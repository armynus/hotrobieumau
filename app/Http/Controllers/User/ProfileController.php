<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $this->currentUser();
        $user->load(['branch.parent', 'transactionOffice', 'department', 'position']);

        $this->syncAvatarSession($request, $user);

        return view('user.profile.show', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $this->currentUser();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($user->id)],
            'citizen_id' => ['nullable', 'string', 'regex:/^\d{9,20}$/', Rule::unique('users', 'citizen_id')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=80,min_height=80,max_width=4000,max_height=4000'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Vui lòng nhập tên hiển thị.',
            'name.max' => 'Tên hiển thị không được quá 100 ký tự.',
            'employee_code.max' => 'Mã nhân viên không được quá 50 ký tự.',
            'employee_code.unique' => 'Mã nhân viên này đã được sử dụng bởi tài khoản khác.',
            'citizen_id.regex' => 'Số CCCD phải gồm từ 9 đến 20 chữ số.',
            'citizen_id.unique' => 'Số CCCD này đã được sử dụng bởi tài khoản khác.',
            'avatar.image' => 'Tệp đã chọn không phải là hình ảnh hợp lệ.',
            'avatar.mimes' => 'Ảnh đại diện chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'avatar.max' => 'Ảnh đại diện không được lớn hơn 2 MB.',
            'avatar.dimensions' => 'Ảnh phải từ 80×80 đến 4000×4000 pixel.',
        ]);

        $oldPath = $user->avatar_path;
        $newPath = null;

        if ($request->hasFile('avatar')) {
            $extension = strtolower($request->file('avatar')->guessExtension() ?: 'jpg');
            $newPath = $request->file('avatar')->storeAs(
                'user-avatars/'.$user->id,
                Str::uuid().'.'.$extension,
                'local'
            );

            if (! $newPath) {
                return back()->withErrors(['avatar' => 'Chưa lưu được ảnh đại diện. Vui lòng thử lại.'])->withInput();
            }
        }

        try {
            $user->name = $validated['name'];
            if ($request->has('employee_code')) {
                $user->employee_code = !empty($validated['employee_code']) ? trim($validated['employee_code']) : null;
            }
            if ($request->has('citizen_id')) {
                $user->citizen_id = !empty($validated['citizen_id']) ? trim($validated['citizen_id']) : null;
            }

            if ($newPath) {
                $user->avatar_path = $newPath;
            } elseif ($request->boolean('remove_avatar')) {
                $user->avatar_path = null;
            }

            $user->save();

            // Đồng bộ sang cache thông tin cán bộ
            $cached = Cache::get("it_officer_profile_{$user->id}", []);
            $cached = is_array($cached) ? $cached : [];
            if ($user->citizen_id) {
                $cached['CCCD'] = $user->citizen_id;
            } else {
                unset($cached['CCCD'], $cached['CMND']);
            }
            if ($user->employee_code) {
                $cached['MaNV'] = $user->employee_code;
            } else {
                unset($cached['MaNV'], $cached['MSNV']);
            }
            Cache::forever("it_officer_profile_{$user->id}", $cached);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        if ($oldPath && $oldPath !== $user->avatar_path && $this->isOwnedAvatarPath($oldPath, $user)) {
            Storage::disk('local')->delete($oldPath);
        }

        $request->session()->put('user_name', $user->name);
        $this->syncAvatarSession($request, $user, true);

        return redirect()->route('profile.show')->with('success', 'Đã cập nhật thông tin cá nhân.');
    }

    public function avatar()
    {
        $user = $this->currentUser();
        $path = $user->avatar_path;

        if (! $path || ! $this->isOwnedAvatarPath($path, $user) || ! Storage::disk('local')->exists($path)) {
            return response()->file(public_path('user_icon.png'), [
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail((int) session('user_id'));
    }

    private function isOwnedAvatarPath(string $path, User $user): bool
    {
        return str_starts_with(str_replace('\\', '/', $path), 'user-avatars/'.$user->id.'/');
    }

    private function syncAvatarSession(Request $request, User $user, bool $refreshVersion = false): void
    {
        $version = $refreshVersion
            ? Str::random(12)
            : ($request->session()->get('user_avatar_version') ?: (optional($user->updated_at)->timestamp ?? time()));

        $request->session()->put([
            'user_has_avatar' => (bool) $user->avatar_path,
            'user_avatar_version' => $version,
        ]);
    }
}
