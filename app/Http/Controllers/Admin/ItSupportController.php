<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItSupportRequest;
use App\Services\ItSupportStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ItSupportController extends Controller
{
    public function manage(Request $request)
    {
        $status = $request->query('status');
        $query = ItSupportRequest::with('user.branch');
        $allowed = ['pending', 'processing', 'resolved', 'closed'];
        $statuses = array_values(array_intersect($allowed, (array) $status));
        if ($statuses) {
            $query->whereIn('status', $statuses);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$term.'%'));
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }
        foreach (['completed_from' => '>=', 'completed_to' => '<='] as $key => $operator) {
            $date = $request->query($key);
            if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                && \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date) {
                $query->whereDate('completed_at', $operator, $date);
            }
        }
        $requests = $query->latest()->paginate(20)->withQueryString();
        $counts = ItSupportRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        return view('admin.it_support.manage', compact('requests', 'counts', 'status', 'statuses'));
    }

    public function show(int $id)
    {
        $ticket = ItSupportRequest::with('user.branch', 'events')->findOrFail($id);
        return view('admin.it_support.show', compact('ticket'));
    }

    public function updateStatus(Request $request, int $id, ItSupportStorage $storage)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,processing,resolved,closed',
            'message' => 'nullable|string|max:3000',
        ]);
        $message = trim($data['message'] ?? '');
        [$ticket, $obsolete] = DB::transaction(function () use ($id, $data, $message, $storage) {
            $ticket = ItSupportRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($ticket->completed_at && in_array($data['status'], ['pending', 'processing'], true)) {
                throw ValidationException::withMessages(['status' => 'Phiếu đã hoàn thiện và lưu trữ. Hãy tạo phiếu mới nếu phát sinh yêu cầu tiếp theo.']);
            }
            if ($message === '' && in_array($data['status'], ['resolved', 'closed'], true)
                && $ticket->status !== $data['status']) {
                throw ValidationException::withMessages(['message' => 'Hãy ghi kết quả xử lý trước khi hoàn thành hoặc đóng phiếu.']);
            }
            if ($message === '' && $ticket->status === $data['status']) {
                throw ValidationException::withMessages(['message' => 'Hãy nhập phản hồi hoặc chọn trạng thái mới.']);
            }
            $before = $ticket->status;
            $ticket->status = $data['status'];
            if ($message !== '' && in_array($data['status'], ['resolved', 'closed'], true)) {
                $ticket->resolution_note = $message;
            }
            if (! $ticket->completed_at && in_array($data['status'], ['resolved', 'closed'], true)) {
                $ticket->completed_at = now();
            }
            $ticket->save();
            $ticket->events()->create([
                'actor_type' => 'admin', 'actor_id' => Session::get('admin_id'),
                'actor_name' => Session::get('admin_name', 'IT'),
                'from_status' => $before, 'to_status' => $ticket->status,
                'message' => $message ?: 'Đã chuyển trạng thái.',
            ]);
            $ticket->unsetRelation('events');
            return [$ticket, $ticket->completed_at ? $storage->secureAndArchive($ticket) : []];
        });
        foreach ($obsolete as $file) {
            Storage::disk($file['disk'])->delete($file['path']);
        }
        return redirect()->route('admin.it_support.show', $ticket)->with('success', 'Đã cập nhật phiếu #'.$ticket->id.'.');
    }

    public function download(int $id, int $file, ItSupportStorage $storage)
    {
        return $storage->download(ItSupportRequest::findOrFail($id), $file);
    }
}
