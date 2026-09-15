@extends('user.layouts.app')
@php
    $user = \App\Models\User::find(Session::get('user_id'));
@endphp
@section('title', 'Quản lý văn bản')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Quản lý văn bản</h1>
            <div class="small text-muted">Tra cứu và xử lý tập trung văn bản đến, đi và quyết định</div>
        </div>
        <div class="d-flex flex-wrap align-items-center mt-2 mt-sm-0">
            @if($user && $user->isClerk())
            <button type="button" class="btn btn-sm btn-success shadow-sm mr-2" data-toggle="modal" data-target="#documentExportModal">
                <i class="fas fa-file-excel mr-1"></i> Xuất sổ Excel
            </button>
            @endif
            @if($user && $user->canUploadDocument())
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary shadow-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-plus fa-sm mr-1"></i> Đăng văn bản
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <a class="dropdown-item" href="{{ route('documents_incoming_register') }}"><i class="fas fa-inbox text-primary mr-2"></i>Văn bản đến</a>
                    <a class="dropdown-item" href="{{ route('documents_outgoing_register') }}"><i class="fas fa-paper-plane text-success mr-2"></i>Văn bản đi</a>
                    <a class="dropdown-item" href="{{ route('documents_decision_register') }}"><i class="fas fa-gavel text-primary mr-2"></i>Quyết định</a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <x-alert-message />

    <div class="document-kind-tabs mb-3" role="group" aria-label="Chọn phân loại văn bản">
        @foreach(['' => ['Tất cả', 'fa-layer-group'], 'incoming' => ['Văn bản đến', 'fa-inbox'], 'outgoing' => ['Văn bản đi', 'fa-paper-plane'], 'decision' => ['Quyết định', 'fa-gavel'], 'unclassified' => ['Chưa phân loại', 'fa-folder']] as $key => [$label, $icon])
        <button type="button" class="document-kind-tab {{ $key === '' ? 'active' : '' }}" data-direction="{{ $key }}" aria-pressed="{{ $key === '' ? 'true' : 'false' }}">
            <i class="fas {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
        </button>
        @endforeach
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Bộ lọc tra cứu nâng cao</h6></div>
        <div class="card-body">
            <form id="filterForm" class="row align-items-end">
                <div class="col-12 col-md-6 col-xl-4 mb-3">
                    <label>Số, ký hiệu văn bản</label>
                    <input type="text" class="form-control" name="keyword" placeholder="Nhập số hoặc ký hiệu văn bản...">
                </div>
                @foreach(['from' => 'Từ', 'to' => 'Đến'] as $key => $label)
                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label>{{ $label }} ngày văn bản</label>
                    <div class="input-group">
                        <input type="text" class="form-control document-date-input" id="document_date_{{ $key }}_display"
                            inputmode="numeric" autocomplete="off" placeholder="dd/mm/yyyy" maxlength="10">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary flatpickr-date-button"
                                data-target="document_date_{{ $key }}_display" aria-label="Chọn ngày">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="date_{{ $key }}">
                </div>
                @endforeach
                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label for="documentReadStatus">Tình trạng đọc</label>
                    <select class="form-control" name="is_read" id="documentReadStatus">
                        <option value="">Tất cả văn bản</option>
                        <option value="0">Chưa đọc</option>
                        <option value="1">Đã đọc</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <button type="button" id="btnFilter" class="btn btn-primary btn-block">
                        <i class="fas fa-search mr-1"></i> Tra cứu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách văn bản</h6>
            <span class="badge badge-primary" id="documentKindLabel">Tất cả văn bản</span>
        </div>
        <div class="card-body">
            <small class="d-block text-muted mb-2">Nhấn tiêu đề cột để sắp xếp tăng/giảm. Ngày văn bản: sớm đến muộn hoặc ngược lại.</small>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light"><tr>
                        <th>STT</th>
                        <th>Phân loại</th>
                        <th>Số, ký hiệu văn bản</th>
                        <th>Trích yếu nội dung</th>
                        <th>Ngày văn bản</th>
                        <th>Hành động</th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('user.page.documents.partials.quick_action_modals')
@if($user && $user->isClerk())
    @include('user.page.documents.partials.export_modal')
@endif
@endsection

@push('styles')
<link href="{{ asset('css/user/document-kind-tabs.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
<style>
    .flatpickr-calendar { border: 0; border-radius: .5rem; box-shadow: 0 .5rem 1.5rem rgba(58, 59, 69, .2); }
    .flatpickr-day.selected, .flatpickr-day.selected:hover { background: #4e73df; border-color: #4e73df; }
    .flatpickr-day.today { border-color: #4e73df; }
    .flatpickr-date-button { min-width: 46px; color: #fff; background: linear-gradient(135deg, #4e73df, #224abe); border-color: #4e73df; }
    .flatpickr-date-button:hover, .flatpickr-date-button:focus { color: #fff; background: linear-gradient(135deg, #3f65d4, #1d3fa3); border-color: #2653d4; }
    #dataTable td { vertical-align: middle; }
    #dataTable .document-direction-cell { min-width: 132px; white-space: nowrap; }
    #dataTable .document-code-cell { min-width: 190px; }
    #dataTable .document-title-cell { min-width: 320px; }
    #dataTable .document-actions { min-width: 158px; }
    .document-export-header { background: linear-gradient(135deg, #16855b, #0f6847); border: 0; }
    .document-export-icon { display: inline-flex; width: 46px; height: 46px; align-items: center; justify-content: center; border-radius: .75rem; background: rgba(255,255,255,.16); font-size: 1.45rem; }
    .document-period-card { display: flex; min-height: 132px; margin: 0; padding: 1rem; cursor: pointer; border: 2px solid #e3e6f0; border-radius: .75rem; background: #fff; align-items: center; justify-content: center; flex-direction: column; text-align: center; transition: .18s ease; }
    .document-period-card i { margin-bottom: .65rem; color: #858796; font-size: 1.5rem; }
    .document-period-card small { margin-top: .25rem; color: #858796; }
    .export-period-radio:checked + .document-period-card { border-color: #1cc88a; background: #effaf6; box-shadow: 0 .25rem .9rem rgba(28,200,138,.14); transform: translateY(-1px); }
    .export-period-radio:checked + .document-period-card i { color: #1cc88a; }
    .document-export-selection { border: 1px solid #dfe5ec; background: #f8f9fc; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/user/document-distribution-editor.js') }}"></script>
<script src="{{ asset('js/user/document-list.js') }}"></script>
@endpush
