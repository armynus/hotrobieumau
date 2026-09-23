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

    <div class="document-kind-shell mb-3">
        <span class="document-kind-caption"><i class="fas fa-filter mr-1" aria-hidden="true"></i> Phân loại nhanh</span>
        <div class="document-kind-tabs" role="group" aria-label="Chọn phân loại văn bản">
            @foreach(['' => ['Tất cả', 'fa-layer-group'], 'incoming' => ['Văn bản đến', 'fa-inbox'], 'outgoing' => ['Văn bản đi', 'fa-paper-plane'], 'decision' => ['Quyết định', 'fa-gavel'], 'unclassified' => ['Chưa phân loại', 'fa-folder']] as $key => [$label, $icon])
            <button type="button" class="document-kind-tab {{ $key === '' ? 'active' : '' }}" data-direction="{{ $key }}" aria-pressed="{{ $key === '' ? 'true' : 'false' }}">
                <i class="fas {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span>
            </button>
            @endforeach
        </div>
    </div>

    <div class="card shadow-sm document-filter-card mb-4">
        <div class="card-header document-filter-header py-3 d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sliders-h mr-2" aria-hidden="true"></i>Bộ lọc tra cứu</h6>
                <small class="text-muted">Lọc nhanh theo số, ký hiệu, ngày văn bản và trạng thái đọc</small>
            </div>
            <button type="button" class="btn btn-sm btn-light border d-none mt-2 mt-sm-0" id="btnResetFilter">
                <i class="fas fa-undo-alt mr-1" aria-hidden="true"></i> Xóa bộ lọc
            </button>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row align-items-end">
                <div class="col-12 col-md-6 col-xl-4 mb-3">
                    <label for="documentKeyword">Số, ký hiệu văn bản</label>
                    <div class="input-group document-keyword-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span></div>
                        <input type="search" class="form-control" id="documentKeyword" name="keyword" placeholder="Ví dụ: 123/NHNo-ĐT-TH" autocomplete="off">
                        <div class="input-group-append d-none" id="clearDocumentKeywordWrap">
                            <button type="button" class="btn btn-light border" id="clearDocumentKeyword" title="Xóa từ khóa" aria-label="Xóa từ khóa"><i class="fas fa-times" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
                @foreach(['from' => 'Từ', 'to' => 'Đến'] as $key => $label)
                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label>{{ $label }} ngày văn bản</label>
                    <div class="input-group">
                        <input type="text" class="form-control document-date-input" id="document_date_{{ $key }}_display"
                            data-date-filter="date_{{ $key }}" inputmode="numeric" autocomplete="off" placeholder="dd/mm/yyyy" maxlength="10"
                            aria-describedby="document_date_{{ $key }}_feedback">
                        <input type="text" class="d-none document-date-picker-anchor" id="document_date_{{ $key }}_picker"
                            tabindex="-1" aria-hidden="true">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary flatpickr-date-button"
                                data-picker-target="document_date_{{ $key }}_picker" aria-label="Chọn ngày">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="invalid-feedback document-date-feedback" id="document_date_{{ $key }}_feedback">Nhập ngày hợp lệ theo định dạng dd/mm/yyyy.</div>
                    <input type="hidden" name="date_{{ $key }}">
                </div>
                @endforeach
                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label for="documentReadStatus">Tình trạng đọc</label>
                    <select class="form-control" name="is_read" id="documentReadStatus">
                        <option value="">Tất cả</option>
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
            <div class="document-filter-footer d-flex flex-wrap align-items-center justify-content-between">
                <div class="document-date-presets" role="group" aria-label="Chọn nhanh khoảng ngày văn bản">
                    <span class="small font-weight-bold text-muted mr-1">Chọn nhanh:</span>
                    <button type="button" class="btn btn-sm btn-light document-date-preset" data-range="today">Hôm nay</button>
                    <button type="button" class="btn btn-sm btn-light document-date-preset" data-range="7days">7 ngày</button>
                    <button type="button" class="btn btn-sm btn-light document-date-preset" data-range="month">Tháng này</button>
                    <button type="button" class="btn btn-sm btn-link document-date-preset" data-range="clear">Xóa ngày</button>
                </div>
                <div class="document-filter-hints mt-2 mt-lg-0">
                    <small class="text-primary d-none" id="documentDateConstraintStatus" role="status" aria-live="polite"></small>
                    <small class="text-muted"><i class="fas fa-keyboard mr-1" aria-hidden="true"></i>Nhấn Enter để lọc ngay</small>
                </div>
            </div>
            <div class="document-active-filters d-none" id="activeFilterBar" role="status" aria-live="polite">
                <span class="document-active-filters__label">Đang lọc:</span>
                <div id="activeFilterChips" class="document-active-filters__chips"></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm document-list-card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-wrap align-items-center">
                <h6 class="m-0 font-weight-bold text-primary mr-2">Danh sách văn bản</h6>
                <span class="badge badge-primary" id="documentKindLabel">Tất cả văn bản</span>
            </div>
            <span class="document-result-count" id="documentResultCount" aria-live="polite"><i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Đang tải</span>
        </div>
        <div class="card-body">
            <div class="document-table-help mb-3"><i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Nhấn tiêu đề cột để sắp xếp. Bộ lọc và trang đang xem được giữ lại khi quay về từ trang chi tiết.</div>
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
<link href="{{ asset('css/user/document-kind-tabs.css') }}?v={{ filemtime(public_path('css/user/document-kind-tabs.css')) }}" rel="stylesheet">
<link href="{{ asset('css/user/document-list.css') }}?v={{ filemtime(public_path('css/user/document-list.css')) }}" rel="stylesheet">
<link href="{{ asset('css/user/document-export.css') }}?v={{ filemtime(public_path('css/user/document-export.css')) }}" rel="stylesheet">
<link href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/user/document-distribution-editor.js') }}"></script>
<script src="{{ asset('js/user/document-list.js') }}?v={{ filemtime(public_path('js/user/document-list.js')) }}"></script>
<script src="{{ asset('js/user/document-export.js') }}?v={{ filemtime(public_path('js/user/document-export.js')) }}"></script>
@endpush
