<?php
namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class FormDraftController extends Controller
{
    public function save(Request $request)
    {
        $userId = Session::get('user_id');
        if (!$userId) return response()->json(['error'=>'Unauthorized'], 401);

        $data = $request->validate([
            'form_key' => 'required|string|max:100',
            'payload' => 'required|string|json|max:200000',
            'revision' => ['required', 'string', 'regex:/\A(?:missing|[a-f0-9]{64})\z/'],
        ]);
        $payload = json_decode($data['payload'], true);
        if (! is_array($payload)) {
            return response()->json(['message' => 'Dữ liệu nháp phải là một đối tượng hoặc mảng.'], 422);
        }

        return DB::transaction(function () use ($userId, $data, $payload) {
            // Khóa user bảo vệ cả trường hợp hai tab cùng tạo nháp lần đầu.
            abort_unless(User::whereKey($userId)->lockForUpdate()->first(['id']), 401);
            $draft = FormDraft::where('user_id', $userId)->where('form_key', $data['form_key'])
                ->lockForUpdate()->first();
            if (! hash_equals($this->revision($draft), $data['revision'])) {
                return response()->json(['message' => 'Bản nháp đã thay đổi ở tab khác. Nội dung đang nhập vẫn được giữ trên máy.'], 409);
            }
            $draft ??= new FormDraft(['user_id' => $userId, 'form_key' => $data['form_key']]);
            $draft->payload = $payload;
            $draft->save();
            $draft->refresh(); // Lấy dạng JSON chuẩn hóa của DB trước khi tạo token.

            return response()->json(['ok' => true, 'revision' => $this->revision($draft)]);
        });
    }

    public function get($formKey)
    {
        $userId = Session::get('user_id');
        if (!$userId) return response()->json(null, 204);

        $draft = FormDraft::query()->where('user_id', $userId)->where('form_key', $formKey)->first();
        return response()->json(['payload' => $draft?->payload, 'revision' => $this->revision($draft)])
            ->header('Cache-Control', 'private, no-store');
    }

    private function revision(?FormDraft $draft): string
    {
        return $draft ? hash('sha256', $draft->id.':'.$draft->getRawOriginal('payload')) : 'missing';
    }
}
