@extends('admin.layouts.app')
@section('title', 'Quản lý hỗ trợ IT')
@push('styles')<link rel="stylesheet" href="{{ asset('css/it-support.css') }}?v={{ filemtime(public_path('css/it-support.css')) }}">@endpush
@section('content')
<div class="container-fluid it-page">
    <header class="it-hero"><div><p class="it-eyebrow">QUẢN TRỊ · HỖ TRỢ IT</p><h1>Phiếu yêu cầu hỗ trợ</h1><p>Tiếp nhận, phản hồi và lưu trữ kết quả xử lý cho các chi nhánh.</p></div></header>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(in_array($status, ['all', 'open'], true))<p class="it-scope-note"><i class="fas fa-layer-group" aria-hidden="true"></i> {{ $status === 'all' ? 'Đang xem tất cả phiếu' : 'Đang xem phiếu đang mở' }}</p>@endif
    <nav class="it-tabs" aria-label="Lọc trạng thái phiếu">@foreach(['pending'=>'Chờ tiếp nhận','processing'=>'Đang xử lý','resolved'=>'Đã xử lý'] as $key=>$label)<a class="{{ $status === $key ? 'active' : '' }}" href="{{ route('admin.it_support.manage', ['status'=>$key]) }}">{{ $label }} <span>{{ $key === 'resolved' ? ($counts['resolved'] ?? 0) + ($counts['closed'] ?? 0) : ($counts[$key] ?? 0) }}</span></a>@endforeach</nav>
    <form class="it-filter-card" action="{{ route('admin.it_support.manage') }}" method="GET">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="it-filter-grid">
            <div class="it-filter-wide"><label for="ticketSearch">Tìm phiếu</label><input id="ticketSearch" class="form-control" name="q" value="{{ $term }}" placeholder="Mã phiếu, tiêu đề, nội dung hoặc người gửi"></div>
            <div><label for="ticketBranch">Chi nhánh</label><select id="ticketBranch" class="form-control" name="branch_id"><option value="">Tất cả chi nhánh</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->branch_name }}</option>@endforeach</select></div>
            <div><label for="ticketCategory">Nhóm hỗ trợ</label><select id="ticketCategory" class="form-control" name="category"><option value="">Tất cả nhóm</option>@foreach(['Phần cứng', 'Phần mềm', 'Mạng', 'Khác'] as $item)<option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>@endforeach</select></div>
            <div><label for="ticketDateType">Lọc theo ngày</label><select id="ticketDateType" class="form-control" name="date_type"><option value="sent" @selected($dateType === 'sent')>Ngày gửi</option>@if(in_array($status, ['all', 'resolved'], true))<option value="processed" @selected($dateType === 'processed')>Ngày xử lý</option>@endif</select></div>
            <div><label for="ticketDateFrom">Từ ngày</label><input id="ticketDateFrom" class="form-control" type="date" name="date_from" value="{{ $dates['date_from'] }}" max="{{ $dates['date_to'] ?: '' }}"></div>
            <div><label for="ticketDateTo">Đến ngày</label><input id="ticketDateTo" class="form-control" type="date" name="date_to" value="{{ $dates['date_to'] }}" min="{{ $dates['date_from'] ?: '' }}"></div>
            <div><label for="ticketSort">Thứ tự</label><select id="ticketSort" class="form-control" name="sort"><option value="newest" @selected($sort === 'newest')>Mới nhất trước</option><option value="oldest" @selected($sort === 'oldest')>Cũ nhất trước</option></select></div>
        </div>
        <div class="it-filter-footer"><span>Đang xem {{ $requests->total() }} phiếu · {{ ['all'=>'Tất cả', 'open'=>'Đang mở', 'pending'=>'Chờ tiếp nhận', 'processing'=>'Đang xử lý', 'resolved'=>'Đã xử lý'][$status] }}</span><div><a class="it-filter-reset" href="{{ route('admin.it_support.manage', ['status' => $status]) }}">Xóa bộ lọc</a><button class="btn it-primary" type="submit"><i class="fas fa-search" aria-hidden="true"></i> Lọc phiếu</button></div></div>
    </form>
    <div class="it-list">@forelse($requests as $ticket)<a class="it-ticket" href="{{ route('admin.it_support.show', $ticket) }}"><div class="it-ticket-main"><span class="it-id">PHIẾU #{{ $ticket->id }}</span><h2>{{ $ticket->title }}</h2><p>{{ \Illuminate\Support\Str::limit($ticket->description ?: 'Phiếu cũ chưa có mô tả.', 130) }}</p><div class="it-meta"><span><i class="fas fa-user" aria-hidden="true"></i> {{ $ticket->user?->name ?: 'Người dùng không còn tồn tại' }}</span><span>{{ $ticket->user?->branch?->branch_name ?: 'Chưa có chi nhánh' }}</span><span>Gửi: {{ $ticket->created_at->format('d/m/Y H:i') }}</span>@if($ticket->completed_at)<span>Xử lý: {{ $ticket->completed_at->format('d/m/Y H:i') }}</span>@endif</div></div><div class="it-ticket-end"><span class="it-status it-status--{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span><span class="it-open">Xem và xử lý <i class="fas fa-arrow-right" aria-hidden="true"></i></span></div></a>@empty<div class="it-empty"><i class="far fa-file-alt" aria-hidden="true"></i><h2>Không có phiếu phù hợp</h2><p>Thử đổi trạng thái, khoảng ngày hoặc từ khóa tìm kiếm.</p></div>@endforelse</div>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
@endsection
