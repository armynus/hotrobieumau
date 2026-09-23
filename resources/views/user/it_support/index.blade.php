@extends('user.layouts.app')
@section('title', 'Hỗ trợ IT')
@push('styles')<link rel="stylesheet" href="{{ asset('css/it-support.css') }}?v={{ filemtime(public_path('css/it-support.css')) }}">@endpush
@section('content')
<div class="container-fluid it-page">
    <header class="it-hero"><div><p class="it-eyebrow">TRUNG TÂM HỖ TRỢ</p><h1>Hỗ trợ IT</h1><p>Theo dõi yêu cầu, trao đổi với bộ phận IT và tra lại kết quả xử lý.</p></div><a href="{{ route('user.it_support.create') }}" class="btn it-primary"><i class="fas fa-plus" aria-hidden="true"></i> Gửi yêu cầu mới</a></header>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <nav class="it-tabs" aria-label="Lọc trạng thái phiếu">
        <a class="{{ !$status ? 'active' : '' }}" href="{{ route('user.it_support.index') }}">Tất cả <span>{{ $counts->sum() }}</span></a>
        <a class="{{ $statuses === ['pending','processing'] ? 'active' : '' }}" href="{{ route('user.it_support.index', ['status' => ['pending','processing']]) }}">Đang mở <span>{{ ($counts['pending'] ?? 0)+($counts['processing'] ?? 0) }}</span></a>
        <a class="{{ $statuses === ['resolved','closed'] ? 'active' : '' }}" href="{{ route('user.it_support.index', ['status' => ['resolved','closed']]) }}">Đã xử lý <span>{{ ($counts['resolved'] ?? 0)+($counts['closed'] ?? 0) }}</span></a>
        @foreach(['pending' => 'Chờ tiếp nhận', 'processing' => 'Đang xử lý', 'resolved' => 'Hoàn thành', 'closed' => 'Đã đóng'] as $key => $label)
            <a class="{{ $status === $key ? 'active' : '' }}" href="{{ route('user.it_support.index', ['status' => $key]) }}">{{ $label }} <span>{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <form class="it-search" action="{{ route('user.it_support.index') }}" method="GET">@foreach($statuses as $selectedStatus)<input type="hidden" name="status[]" value="{{ $selectedStatus }}">@endforeach<label class="sr-only" for="ticketSearch">Tìm phiếu của tôi</label><input id="ticketSearch" class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm mã phiếu hoặc tiêu đề"><button class="btn it-primary" type="submit"><i class="fas fa-search" aria-hidden="true"></i> Tìm</button></form>
    <div class="it-list">
        @forelse($requests as $ticket)
            <a class="it-ticket" href="{{ route('user.it_support.show', $ticket) }}">
                <div class="it-ticket-main"><span class="it-id">PHIẾU #{{ $ticket->id }}</span><h2>{{ $ticket->title }}</h2><p>{{ \Illuminate\Support\Str::limit($ticket->description ?: 'Phiếu cũ chưa có mô tả.', 130) }}</p><div class="it-meta"><span><i class="far fa-clock" aria-hidden="true"></i> {{ $ticket->created_at->format('d/m/Y H:i') }}</span>@if($ticket->category)<span>{{ $ticket->category }}</span>@endif @if(count($ticket->attachmentFiles()))<span><i class="fas fa-paperclip" aria-hidden="true"></i> {{ count($ticket->attachmentFiles()) }} tệp</span>@endif</div></div>
                <div class="it-ticket-end"><span class="it-status it-status--{{ $ticket->status }}">{{ $ticket->statusLabel() }}</span><span class="it-open">Xem chi tiết <i class="fas fa-arrow-right" aria-hidden="true"></i></span></div>
            </a>
        @empty
            <div class="it-empty"><i class="far fa-file-alt" aria-hidden="true"></i><h2>Chưa có phiếu nào</h2><p>Gửi yêu cầu khi cần hỗ trợ thiết bị, phần mềm hoặc kết nối mạng.</p><a class="btn it-primary" href="{{ route('user.it_support.create') }}">Tạo phiếu đầu tiên</a></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
@endsection
