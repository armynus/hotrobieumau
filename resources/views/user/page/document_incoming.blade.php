@extends('user.layouts.app')
@php
$user = \App\Models\User::find(Session::get('user_id'));
@endphp
@section('title', 'Văn bản đến')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">
                <i class="fas fa-inbox text-primary mr-2" aria-hidden="true"></i>Văn bản đến
            </h1>
            <div class="small text-muted">Văn bản mới đăng tải và được chuyển tiếp đến tôi cần xử lý</div>
        </div>
        <div class="d-flex flex-wrap align-items-center mt-2 mt-sm-0">
            <button type="button" class="btn btn-sm btn-light border shadow-sm mr-2" id="btnRefresh" title="Tải lại danh sách">
                <i class="fas fa-sync-alt mr-1"></i> Làm mới
            </button>
            <a href="{{ route('documents_forward') }}" class="btn btn-sm btn-outline-primary shadow-sm mr-2">
                <i class="fas fa-layer-group mr-1"></i> Tất cả văn bản
            </a>
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

    <!-- 3 Stat Cards Row -->
    <div class="row incoming-stats-row">
        <!-- Card 1: Tổng văn bản đến -->
        <div class="col-xl-4 col-md-4 mb-3">
            <div class="incoming-stat-card card-total h-100 p-3" id="cardTotal" style="cursor: pointer;" title="Bấm để xem tất cả">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Tổng văn bản đến
                        </div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="statTotalIncoming">
                            <i class="fas fa-spinner fa-spin fa-sm"></i>
                        </div>
                        <div class="small text-muted mt-1">Được chuyển tiếp và tiếp nhận</div>
                    </div>
                    <div class="stat-icon-circle">
                        <i class="fas fa-inbox"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Chưa đọc (Cần xử lý) -->
        <div class="col-xl-4 col-md-4 mb-3">
            <div class="incoming-stat-card card-unread h-100 p-3" id="cardUnread" style="cursor: pointer;" title="Bấm để xem văn bản chưa đọc">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1 d-flex align-items-center">
                            <span class="stat-badge-pulse"></span> Chưa đọc
                        </div>
                        <div class="h4 mb-0 font-weight-bold text-warning" id="statUnreadIncoming">
                            <i class="fas fa-spinner fa-spin fa-sm"></i>
                        </div>
                        <div class="small text-muted mt-1">Văn bản mới cần xem hoặc xử lý</div>
                    </div>
                    <div class="stat-icon-circle">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Đã đọc (Đã xử lý) -->
        <div class="col-xl-4 col-md-4 mb-3">
            <div class="incoming-stat-card card-read h-100 p-3" id="cardRead" style="cursor: pointer;" title="Bấm để xem văn bản đã đọc">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Đã đọc (Đã xử lý)
                        </div>
                        <div class="h4 mb-0 font-weight-bold text-success" id="statReadIncoming">
                            <i class="fas fa-spinner fa-spin fa-sm"></i>
                        </div>
                        <div class="small text-muted mt-1">Văn bản đã được tiếp nhận</div>
                    </div>
                    <div class="stat-icon-circle">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Tabs Bar -->
    <div class="incoming-status-tabs-container">
        <div class="incoming-status-tabs" role="group" aria-label="Lọc theo trạng thái xử lý">
            <button type="button" class="incoming-status-tab" data-read="0">
                <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                <span>Chưa đọc</span>
                <span class="badge badge-warning badge-count" id="tabCountUnread">0</span>
            </button>
            <button type="button" class="incoming-status-tab" data-read="">
                <i class="fas fa-layer-group mr-1" aria-hidden="true"></i>
                <span>Tất cả</span>
                <span class="badge badge-light badge-count border" id="tabCountTotal">0</span>
            </button>
            <button type="button" class="incoming-status-tab" data-read="1">
                <i class="fas fa-check mr-1" aria-hidden="true"></i>
                <span>Đã đọc</span>
                <span class="badge badge-success badge-count" id="tabCountRead">0</span>
            </button>
        </div>
        <div class="small text-muted">
            <i class="fas fa-info-circle mr-1"></i> Bấm văn bản để xem chi tiết và tự động đánh dấu đã đọc
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm document-filter-card mb-4">
        <div class="card-header document-filter-header py-3 d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-search mr-1" aria-hidden="true"></i>Bộ lọc tra cứu văn bản đến
                </h6>
                <small class="text-muted">Tìm kiếm theo số, ký hiệu, trích yếu và ngày tiếp nhận</small>
            </div>
            <button type="button" class="btn btn-sm btn-light border mt-2 mt-sm-0" id="btnResetFilter">
                <i class="fas fa-undo-alt mr-1" aria-hidden="true"></i> Xóa lọc
            </button>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row align-items-end">
                <div class="col-12 col-md-6 col-xl-4 mb-3">
                    <label for="documentKeyword">Số, ký hiệu hoặc trích yếu</label>
                    <div class="input-group document-keyword-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span></div>
                        <input type="search" class="form-control" id="documentKeyword" name="keyword" placeholder="Nhập số, ký hiệu hoặc trích yếu..." autocomplete="off">
                        <div class="input-group-append d-none" id="clearDocumentKeywordWrap">
                            <button type="button" class="btn btn-light border" id="clearDocumentKeyword" title="Xóa từ khóa" aria-label="Xóa từ khóa"><i class="fas fa-times" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label>Từ ngày</label>
                    <div class="input-group">
                        <input type="text" class="form-control document-date-input" id="doc_date_from_display"
                            data-date-filter="date_from" inputmode="numeric" autocomplete="off" placeholder="dd/mm/yyyy" maxlength="10">
                        <input type="text" class="d-none" id="doc_date_from_picker" tabindex="-1" aria-hidden="true">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary flatpickr-date-btn" data-picker-target="doc_date_from_picker" aria-label="Chọn ngày">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="date_from">
                </div>

                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label>Đến ngày</label>
                    <div class="input-group">
                        <input type="text" class="form-control document-date-input" id="doc_date_to_display"
                            data-date-filter="date_to" inputmode="numeric" autocomplete="off" placeholder="dd/mm/yyyy" maxlength="10">
                        <input type="text" class="d-none" id="doc_date_to_picker" tabindex="-1" aria-hidden="true">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary flatpickr-date-btn" data-picker-target="doc_date_to_picker" aria-label="Chọn ngày">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="date_to">
                </div>

                <div class="col-12 col-md-6 col-xl-2 mb-3">
                    <label for="directionFilter">Phân loại</label>
                    <select class="form-control" name="direction" id="directionFilter">
                        <option value="">Tất cả phân loại</option>
                        <option value="incoming">Văn bản đến</option>
                        <option value="outgoing">Văn bản đi chuyển tiếp</option>
                        <option value="decision">Quyết định chuyển tiếp</option>
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
                    <small class="text-muted"><i class="fas fa-keyboard mr-1" aria-hidden="true"></i>Nhấn Enter để lọc ngay</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm document-incoming-card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h6 class="m-0 font-weight-bold text-primary mr-2">
                    <i class="fas fa-list-alt mr-1"></i> Danh sách văn bản đến
                </h6>
            </div>
            <span class="small font-weight-bold text-muted" id="tableLoadingCount">
                <i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="incomingTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">STT</th>
                            <th class="text-center" style="width: 135px;">Trạng thái</th>
                            <th class="text-center" style="width: 110px;">Phân loại</th>
                            <th style="width: 170px;">Số, ký hiệu</th>
                            <th>Trích yếu nội dung</th>
                            <th class="text-center" style="width: 120px;">Ngày tiếp nhận</th>
                            <th class="text-center" style="width: 130px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('user.page.documents.partials.quick_action_modals')
@endsection

@push('styles')
<link href="{{ asset('css/user/document-kind-tabs.css') }}?v={{ filemtime(public_path('css/user/document-kind-tabs.css')) }}" rel="stylesheet">
<link href="{{ asset('css/user/document-list.css') }}?v={{ filemtime(public_path('css/user/document-list.css')) }}" rel="stylesheet">
<link href="{{ asset('css/user/document-incoming.css') }}?v={{ filemtime(public_path('css/user/document-incoming.css')) }}" rel="stylesheet">
<link href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/user/document-distribution-editor.js') }}"></script>
<script src="{{ asset('js/user/document-incoming.js') }}?v={{ filemtime(public_path('js/user/document-incoming.js')) }}"></script>
@endpush