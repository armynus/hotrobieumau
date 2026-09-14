@extends('user.layouts.app')
@section('title', 'Sổ văn bản')
@section('content')
<div class="container-fluid" id="ledgerPage" data-import-url="{{ route('documents_ledger_import') }}" data-candidates-url="{{ route('documents_ledger_candidates') }}" data-create-url="{{ route('documents_ledger_create') }}" data-next-url="{{ route('documents_ledger_next_number') }}" data-register-url="{{ url('documents/ledger') }}">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
        <div><h1 class="h3 text-gray-800 mb-1">Sổ văn bản</h1><div class="text-muted">{{ $clerk->branch->branch_name }} · Năm {{ $year }}</div></div>
        <div class="mt-3 mt-md-0">
            <button class="btn btn-primary mr-1" data-toggle="modal" data-target="#ledgerEntryModal" id="newLedgerEntry"><i class="fas fa-pen-nib mr-1"></i> Ghi mới vào sổ</button>
            <button class="btn btn-outline-primary mr-1" data-toggle="modal" data-target="#ledgerEntryModal" id="addLedgerEntry"><i class="fas fa-book-medical mr-1"></i> Đưa văn bản vào sổ</button>
            <button class="btn btn-primary" data-toggle="modal" data-target="#ledgerImportModal"><i class="fas fa-file-import mr-1"></i> Nhập Excel</button>
        </div>
    </div>
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row">
        @foreach($bookLabels as $key => $label)
        <div class="col-md-4 mb-3">
            <a class="card h-100 shadow-sm ledger-book {{ $key === $book ? 'selected' : '' }}" href="{{ route('documents_ledger', ['year' => $year, 'book' => $key]) }}">
                <div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-primary font-weight-bold">{{ $label }}</div><div class="h3 text-gray-800 mb-0 mt-2">{{ number_format($counts[$key] ?? 0) }}</div></div><i class="fas fa-book fa-2x text-gray-300"></i></div>
            </a>
        </div>
        @endforeach
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('documents_ledger') }}" class="form-row align-items-end">
                <div class="form-group col-md-2"><label for="ledgerYear">Năm sổ</label><input id="ledgerYear" class="form-control" type="number" name="year" min="2000" max="2100" value="{{ $year }}" required></div>
                <div class="form-group col-md-3"><label for="ledgerBook">Loại sổ</label><select class="form-control" name="book" id="ledgerBook">@foreach($bookLabels as $key => $label)<option value="{{ $key }}" @selected($book === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group col-md-5"><label for="ledgerKeyword">Tra cứu trong sổ</label><input class="form-control" id="ledgerKeyword" name="q" value="{{ $keyword }}" placeholder="Số sổ, số ký hiệu hoặc trích yếu" maxlength="255"></div>
                <div class="form-group col-md-2"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Tra cứu</button></div>
            </form>
            <div class="d-flex flex-wrap justify-content-between small text-muted"><span>Số tiếp theo dự kiến: <strong class="text-primary">{{ $nextNumber }}</strong> · Cấp riêng theo năm và loại sổ.</span><span>Chỉ các dòng đã vào sổ được đưa vào file Excel.</span></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 ledger-table">
                <thead class="bg-light"><tr><th>Số sổ</th><th>Ngày vào sổ</th><th>Số, ký hiệu văn bản</th><th>Trích yếu</th><th>Ngày văn bản</th><th></th></tr></thead>
                <tbody>@forelse($entries as $entry)
                    <tr>
                        <td class="font-weight-bold">{{ $entry->number }}</td><td>{{ $entry->registered_date?->format('d/m/Y') ?? '—' }}</td>
                        <td><a href="{{ route('document_detail', $entry->document_id) }}">{{ $entry->document_code ?: $entry->document->document_code ?: 'Chưa có số, ký hiệu' }}</a></td>
                        <td class="ledger-title">{{ $entry->document->title ?: 'Chưa cập nhật trích yếu' }}@if($entry->source_name)<small class="d-block text-muted">{{ $entry->source_sheet }} · Dòng {{ $entry->source_row }}</small>@endif</td>
                        <td>{{ $entry->document->issued_date?->format('d/m/Y') ?? '—' }}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-primary edit-ledger-entry" title="Chỉnh thông tin vào sổ" aria-label="Chỉnh thông tin vào sổ" data-entry="{{ json_encode($entry->form_data, JSON_UNESCAPED_UNICODE) }}"><i class="fas fa-edit"></i></button></td>
                    </tr>
                @empty<tr><td colspan="6" class="text-center text-muted py-5">Chưa có văn bản trong sổ này. Nhập Excel hoặc vào sổ văn bản đã có trong hệ thống.</td></tr>@endforelse</tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $entries->links('pagination::bootstrap-4') }}</div>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 font-weight-bold text-success"><i class="fas fa-file-excel mr-1"></i> Xuất sổ văn bản</h2>
            <form method="POST" action="{{ route('documents_export') }}" class="form-row align-items-end" id="ledgerExportForm">@csrf
                <div class="form-group col-md-3"><label>Loại sổ</label><select name="direction" class="form-control"><option value="incoming" @selected($book === 'incoming')>Sổ văn bản đến</option><option value="outgoing" @selected($book !== 'incoming')>Sổ văn bản đi (2 sheet)</option></select></div>
                <div class="form-group col-md-2"><label>Năm</label><input class="form-control" name="year" type="number" min="2000" max="2100" value="{{ $year }}" required></div>
                <div class="form-group col-md-2"><label>Kỳ xuất</label><select class="form-control" name="period_type"><option value="month">Theo tháng</option><option value="quarter">Theo quý</option><option value="year" selected>Cả năm</option></select></div>
                <div class="form-group col-md-2 export-month d-none"><label>Tháng</label><select class="form-control" name="month">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($m === now()->month)>{{ $m }}</option>@endfor</select></div>
                <div class="form-group col-md-2 export-quarter d-none"><label>Quý</label><select class="form-control" name="quarter">@for($q=1;$q<=4;$q++)<option value="{{ $q }}" @selected($q === now()->quarter)>{{ $q }}</option>@endfor</select></div>
                <div class="form-group col-md-3"><button class="btn btn-success btn-block"><i class="fas fa-download mr-1"></i> Tải Excel</button></div>
            </form>
            <small class="text-muted">Văn bản đi tách sheet thông thường và quyết định. Ngày lọc là ngày vào sổ, không phải ngày ghi trên văn bản.</small>
        </div>
    </div>
</div>

@include('user.page.documents.partials.ledger_import_modal')

@include('user.page.documents.partials.ledger_entry_modal')
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/user/document-ledger-entry.css') }}">
<style>.ledger-book{border-left:4px solid #d1d3e2;text-decoration:none!important}.ledger-book.selected{border-left-color:#4e73df;background:#f5f7ff}.ledger-table th,.ledger-table td{vertical-align:middle}.ledger-table th{white-space:nowrap}.ledger-title{min-width:260px;max-width:550px}.ledger-import-issues{max-height:230px;overflow:auto}.ledger-stats{display:flex;gap:12px;flex-wrap:wrap}.ledger-stats>div{padding:8px 12px;background:#f8f9fc;border-radius:6px}</style>
@endpush
@push('scripts')
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script src="{{ asset('js/user/document-ledger.js') }}"></script>
<script src="{{ asset('js/user/document-ledger-entry.js') }}"></script>
@endpush
