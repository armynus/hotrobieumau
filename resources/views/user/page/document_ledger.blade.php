@extends('user.layouts.app')
@section('title', 'Sổ văn bản')
@section('content')
<div class="container-fluid" id="ledgerPage" data-check-only="{{ $checkOnly ? 1 : 0 }}" data-table-url="{{ route('documents_ledger', ['year' => $year, 'book' => $book, 'check' => $checkOnly ? 1 : 0]) }}" data-update-url="{{ url('documents/ledger/entries') }}" data-import-url="{{ route('documents_ledger_import') }}" data-candidates-url="{{ route('documents_ledger_candidates') }}" data-create-url="{{ route('documents_ledger_create') }}" data-next-url="{{ route('documents_ledger_next_number') }}" data-next-number="{{ $nextNumber }}" data-register-url="{{ url('documents/ledger') }}">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div><h1 class="h3 text-gray-800 mb-1">Sổ văn bản</h1><div class="text-muted">{{ $clerk->branch->branch_name }} · Năm {{ $year }}</div></div>
        <div class="mt-3 mt-md-0">
            <a class="btn {{ $checkOnly ? 'btn-primary' : 'btn-outline-primary' }} mr-1" id="ledgerCheckButton" href="{{ route('documents_ledger', ['year' => $year, 'book' => $book, 'q' => $checkOnly ? null : $keyword, 'check' => $checkOnly ? 0 : 1]) }}"><i class="fas {{ $checkOnly ? 'fa-sign-out-alt' : 'fa-clipboard-check' }} mr-1" aria-hidden="true"></i> {{ $checkOnly ? 'Thoát kiểm tra' : 'Kiểm tra sổ' }}</a>
            <button class="btn btn-primary mr-1" data-toggle="modal" data-target="#ledgerEntryModal" id="newLedgerEntry"><i class="fas fa-pen-nib mr-1"></i> Ghi mới vào sổ</button>
            <button class="btn btn-outline-primary mr-1" data-toggle="modal" data-target="#ledgerEntryModal" id="addLedgerEntry"><i class="fas fa-book-medical mr-1"></i> Đưa văn bản vào sổ</button>
            <button class="btn btn-primary" data-toggle="modal" data-target="#ledgerImportModal"><i class="fas fa-file-import mr-1"></i> Nhập Excel</button>
        </div>
    </div>
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row">
        @foreach($bookLabels as $key => $label)
        <div class="col-md-4 mb-3">
            <a class="card h-100 shadow-sm ledger-book {{ $key === $book ? 'selected' : '' }}" href="{{ route('documents_ledger', ['year' => $year, 'book' => $key, 'q' => $keyword, 'check' => $checkOnly ? 1 : 0]) }}" @if($key === $book) aria-current="page" @endif>
                <div class="card-body d-flex justify-content-between align-items-center"><div><div class="ledger-book-label font-weight-bold">{{ $label }}</div><div class="h3 ledger-book-count mb-0 mt-2">{{ number_format($counts[$key] ?? 0) }}</div>@if($key === $book)<small class="ledger-book-active"><i class="fas fa-check-circle mr-1"></i> Đang xem</small>@endif</div><span class="ledger-book-icon"><i class="fas {{ ['incoming' => 'fa-inbox', 'outgoing' => 'fa-paper-plane', 'decision' => 'fa-gavel'][$key] }}" aria-hidden="true"></i></span></div>
            </a>
        </div>
        @endforeach
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('documents_ledger') }}" class="form-row align-items-end">
                <input type="hidden" name="check" value="{{ $checkOnly ? 1 : 0 }}">
                <div class="form-group col-md-2"><label for="ledgerYear">Năm sổ</label><input id="ledgerYear" class="form-control" type="number" name="year" min="2000" max="2100" value="{{ $year }}" required></div>
                <div class="form-group col-md-3"><label for="ledgerBook">Loại sổ</label><select class="form-control" name="book" id="ledgerBook">@foreach($bookLabels as $key => $label)<option value="{{ $key }}" @selected($book === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group col-md-5"><label for="ledgerKeyword">Tra cứu trong sổ</label><input class="form-control" id="ledgerKeyword" name="q" value="{{ $keyword }}" placeholder="Số sổ, số ký hiệu hoặc trích yếu" maxlength="255"></div>
                <div class="form-group col-md-2"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Tra cứu</button></div>
            </form>
            <div class="d-flex flex-wrap justify-content-between small text-muted"><span>Số tiếp theo dự kiến: <strong class="text-primary">{{ $nextNumber }}</strong> · Cấp riêng theo năm và loại sổ.</span><span>Chỉ các dòng đã vào sổ được đưa vào file Excel.</span></div>
        </div>
        <section class="ledger-check-summary mx-3 mb-3" aria-labelledby="ledgerCheckHeading">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h2 class="h6 font-weight-bold mb-1" id="ledgerCheckHeading"><i class="fas fa-clipboard-check mr-1" aria-hidden="true"></i> Kiểm tra {{ mb_strtolower($bookLabels[$book]) }} · {{ $year }}</h2>
                    <div id="ledgerCheckStatus" role="status" aria-live="polite">Đang kiểm tra thông tin trong sổ…</div>
                </div>
                @if($checkOnly)
                    <a class="btn btn-sm btn-primary mt-2 mt-md-0" id="ledgerExitCheck" href="{{ route('documents_ledger', ['year' => $year, 'book' => $book, 'check' => 0]) }}"><i class="fas fa-sign-out-alt mr-1" aria-hidden="true"></i> Thoát kiểm tra</a>
                @else
                    <a class="btn btn-sm btn-outline-primary mt-2 mt-md-0" href="{{ route('documents_ledger', ['year' => $year, 'book' => $book, 'check' => 1]) }}">Xem các dòng thiếu <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></a>
                @endif
            </div>
            <p class="small mb-0 mt-2 text-muted">Nhắc các ô còn trống: số sổ, số/ký hiệu, trích yếu, ngày đến/chuyển và ngày văn bản. Tác giả, người ký và các ô tùy chọn không bắt buộc. Kiểm tra chỉ đọc dữ liệu, không tự sửa hoặc đánh lại số.</p>
        </section>
        <div class="px-3 pb-3 ledger-table-container">
            @if($checkOnly)<p class="small font-weight-bold text-primary mb-2">Chỉ hiển thị dòng thiếu thông tin. Bấm bút chì để bổ sung; lưu xong dòng đã đầy đủ sẽ tự rời danh sách này.</p>@endif
            <small class="d-block text-muted mb-2">Nhấn tiêu đề cột để đổi thứ tự tăng/giảm: số sổ, ngày vào sổ hoặc ngày văn bản.</small>
            <table id="ledgerTable" class="table table-hover ledger-table w-100">
                <thead class="bg-light"><tr><th>Số sổ</th><th>Ngày vào sổ</th><th>Số, ký hiệu văn bản</th><th>Trích yếu</th><th>Ngày văn bản</th><th>Thông tin còn thiếu</th><th>Thao tác</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 font-weight-bold text-success"><i class="fas fa-file-excel mr-1"></i> Xuất sổ văn bản</h2>
            <form method="POST" action="{{ route('documents_export') }}" class="form-row align-items-end" id="ledgerExportForm" data-document-export-form>@csrf
                <input type="hidden" name="background" value="1">
                <div class="form-group col-md-3"><label>Loại sổ</label><select name="direction" class="form-control"><option value="incoming" @selected($book === 'incoming')>Sổ văn bản đến</option><option value="outgoing" @selected($book !== 'incoming')>Sổ văn bản đi (2 sheet)</option></select></div>
                <div class="form-group col-md-2"><label>Năm</label><input class="form-control" name="year" type="number" min="2000" max="2100" value="{{ $year }}" required></div>
                <div class="form-group col-md-2"><label>Kỳ xuất</label><select class="form-control" name="period_type"><option value="month">Theo tháng</option><option value="quarter">Theo quý</option><option value="year" selected>Cả năm</option></select></div>
                <div class="form-group col-md-2 export-month d-none"><label>Tháng</label><select class="form-control" name="month">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($m === now()->month)>{{ $m }}</option>@endfor</select></div>
                <div class="form-group col-md-2 export-quarter d-none"><label>Quý</label><select class="form-control" name="quarter">@for($q=1;$q<=4;$q++)<option value="{{ $q }}" @selected($q === now()->quarter)>{{ $q }}</option>@endfor</select></div>
                <div class="form-group col-md-3"><button class="btn btn-success btn-block" data-document-export-submit><i class="fas fa-download mr-1"></i> Tạo file Excel</button></div>
            </form>
            @include('user.page.documents.partials.export_status')
            <small class="text-muted">Văn bản đi tách sheet thông thường và quyết định. Ngày lọc là ngày vào sổ, không phải ngày ghi trên văn bản.</small>
        </div>
    </div>
</div>

@include('user.page.documents.partials.ledger_import_modal')

@include('user.page.documents.partials.ledger_entry_modal')
@include('user.page.documents.partials.ledger_slip_modal')
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/user/document-ledger-entry.css') }}?v={{ filemtime(public_path('css/user/document-ledger-entry.css')) }}">
<link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/user/document-ledger-table.css') }}?v={{ filemtime(public_path('css/user/document-ledger-table.css')) }}">
<style>.ledger-table th,.ledger-table td{vertical-align:middle}.ledger-table th{white-space:nowrap}.ledger-title{min-width:260px;max-width:550px}.ledger-import-issues{max-height:230px;overflow:auto}.ledger-stats{display:flex;gap:12px;flex-wrap:wrap}.ledger-stats>div{padding:8px 12px;background:#f8f9fc;border-radius:6px}</style>
@endpush
@push('scripts')
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/user/document-ledger-table.js') }}?v={{ filemtime(public_path('js/user/document-ledger-table.js')) }}"></script>
<script src="{{ asset('js/user/document-ledger.js') }}"></script>
<script src="{{ asset('js/user/document-ledger-entry.js') }}?v={{ filemtime(public_path('js/user/document-ledger-entry.js')) }}"></script>
<script src="{{ asset('js/user/document-ledger-slip.js') }}?v={{ filemtime(public_path('js/user/document-ledger-slip.js')) }}"></script>
<script src="{{ asset('js/user/document-export.js') }}?v={{ filemtime(public_path('js/user/document-export.js')) }}"></script>
@endpush
