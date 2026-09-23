<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ItSupportRequest;
use App\Services\ItSupportStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ItSupportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = ItSupportRequest::where('user_id', Session::get('user_id'));
        $allowed = ['pending', 'processing', 'resolved', 'closed'];
        $statuses = array_values(array_intersect($allowed, (array) $status));
        if ($statuses) {
            $query->whereIn('status', $statuses);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%');
                if (ctype_digit($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }
        $requests = $query->latest()->paginate(10)->withQueryString();
        $counts = ItSupportRequest::where('user_id', Session::get('user_id'))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        return view('user.it_support.index', compact('requests', 'counts', 'status', 'statuses'));
    }

    public function create()
    {
        return view('user.it_support.create');
    }

    public function store(Request $request, ItSupportStorage $storage)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:5000',
            'contact_phone' => 'nullable|string|max:20',
            'category' => 'nullable|in:Phần cứng,Phần mềm,Mạng,Khác',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
        ]);
        $ticket = DB::transaction(function () use ($data, $request, $storage) {
            $ticket = ItSupportRequest::create([
                'user_id' => Session::get('user_id'),
                'title' => $data['title'], 'description' => $data['description'],
                'category' => $data['category'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'status' => 'pending',
            ]);
            $storage->storeUploads($ticket, $request->file('attachments', []));
            $ticket->events()->create([
                'actor_type' => 'user', 'actor_id' => Session::get('user_id'),
                'actor_name' => Session::get('user_name', 'Người gửi'),
                'to_status' => 'pending', 'message' => 'Đã gửi phiếu yêu cầu.',
            ]);
            return $ticket;
        });
        return redirect()->route('user.it_support.show', $ticket)->with('success', 'Đã gửi phiếu hỗ trợ #'.$ticket->id.'.');
    }

    public function show(int $id)
    {
        $ticket = $this->ownedTicket($id)->load('events');
        return view('user.it_support.show', compact('ticket'));
    }

    public function reply(Request $request, int $id, ItSupportStorage $storage)
    {
        $data = $request->validate(['message' => 'required|string|min:3|max:3000']);
        $ticket = $this->ownedTicket($id);
        if ($ticket->status === 'closed') {
            return back()->withErrors(['message' => 'Phiếu đã đóng. Vui lòng tạo phiếu mới nếu cần hỗ trợ tiếp.']);
        }
        $ticket->events()->create([
            'actor_type' => 'user', 'actor_id' => Session::get('user_id'),
            'actor_name' => Session::get('user_name', 'Người gửi'),
            'from_status' => $ticket->status, 'to_status' => $ticket->status,
            'message' => $data['message'],
        ]);
        $ticket->unsetRelation('events');
        $storage->writeManifest($ticket);
        return back()->with('success', 'Đã bổ sung thông tin cho phiếu.');
    }

    public function download(int $id, int $file, ItSupportStorage $storage)
    {
        return $storage->download($this->ownedTicket($id), $file);
    }

    private function ownedTicket(int $id): ItSupportRequest
    {
        return ItSupportRequest::where('user_id', Session::get('user_id'))->findOrFail($id);
    }
}
