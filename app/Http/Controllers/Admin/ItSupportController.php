<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branches;
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
        $status = $request->query('status', 'all');
        if ($status === 'closed') {
            $status = 'resolved';
        }
        $status = in_array($status, ['all', 'open', 'pending', 'processing', 'resolved'], true) ? $status : 'all';
        $query = ItSupportRequest::with('user.branch');
        if ($status !== 'all') {
            $query->whereIn('status', match ($status) {
                'open' => ['pending', 'processing'],
                'resolved' => ['resolved', 'closed'],
                default => [$status],
            });
        }
        $term = $request->query('q');
        $term = is_string($term) ? trim($term) : '';
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$term.'%'));
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }
        $branchId = $request->query('branch_id');
        if (is_string($branchId) && ctype_digit($branchId) && (int) $branchId > 0) {
            $query->whereHas('user', fn ($user) => $user->where('branch_id', (int) $branchId));
        } else {
            $branchId = '';
        }
        $category = $request->query('category');
        if (in_array($category, ['Phần cứng', 'Phần mềm', 'Mạng', 'Khác'], true)) {
            $query->where('category', $category);
        } else {
            $category = '';
        }
        $dateType = $request->query('date_type');
        if ($dateType === null && ($request->has('completed_from') || $request->has('completed_to'))) {
            $dateType = 'processed';
        }
        $dateType = $dateType === 'processed' && in_array($status, ['all', 'resolved'], true) ? 'processed' : 'sent';
        $dateColumn = $dateType === 'processed' ? 'completed_at' : 'created_at';
        $dates = ['date_from' => '', 'date_to' => ''];
        foreach (['date_from' => '>=', 'date_to' => '<'] as $key => $operator) {
            $date = $request->query($key);
            if (! is_string($date) && $dateType === 'processed') {
                $date = $request->query($key === 'date_from' ? 'completed_from' : 'completed_to');
            }
            if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                && \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date) {
                $dates[$key] = $date;
                $boundary = new \DateTimeImmutable($date);
                $query->where($dateColumn, $operator, $key === 'date_to'
                    ? $boundary->modify('+1 day')->format('Y-m-d 00:00:00')
                    : $boundary->format('Y-m-d 00:00:00'));
            }
        }
        $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc');
        $requests = $query->paginate(20)->withQueryString();
        $counts = ItSupportRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $branches = Branches::orderBy('branch_name')->get(['id', 'branch_name']);

        return view('admin.it_support.manage', compact('requests', 'counts', 'status', 'branches', 'dateType', 'sort', 'term', 'branchId', 'category', 'dates'));
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
            if ($data['status'] === 'closed' && $ticket->status !== 'closed') {
                throw ValidationException::withMessages(['status' => 'Hãy chọn một trong ba trạng thái đang dùng.']);
            }
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
