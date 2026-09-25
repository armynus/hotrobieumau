@extends('user.layouts.app')
@section('title', 'Tra cứu xã/phường trước & sau sáp nhập')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/merger-lookup.css') }}?v={{ filemtime(public_path('css/merger-lookup.css')) }}">
@endpush
@section('content')
<div class="merger-lookup container py-4" id="mergerLookup" data-options="{{ route('geography.options') }}" data-detail="{{ route('geography.detail') }}">
    <header class="merger-heading">
        <span class="merger-heading-icon" aria-hidden="true"><i class="fas fa-map-marked-alt"></i></span>
        <div><h1>Tra cứu xã/phường</h1><p>Tìm địa bàn trước và sau sáp nhập theo cả hai chiều.</p></div>
    </header>
    @if(!$dataset)
        <div class="alert alert-info">Chưa có bộ dữ liệu tra cứu. Quản trị viên cần nhập dữ liệu xã/phường.</div>
    @else
        <div class="merger-toolbar">
            <ul class="nav merger-tabs" role="tablist" aria-label="Chiều tra cứu">
                <li class="nav-item"><a class="nav-link active" id="pre-tab" data-toggle="tab" href="#pre" role="tab" aria-controls="pre" aria-selected="true"><i class="fas fa-arrow-right mr-2" aria-hidden="true"></i>Trước sáp nhập</a></li>
                <li class="nav-item"><a class="nav-link" id="post-tab" data-toggle="tab" href="#post" role="tab" aria-controls="post" aria-selected="false"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Sau sáp nhập</a></li>
            </ul>
            <button type="button" class="merger-reset" id="mergerReset"><i class="fas fa-undo-alt mr-2" aria-hidden="true"></i>Xóa lựa chọn</button>
        </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="pre" role="tabpanel" aria-labelledby="pre-tab">
                <div class="merger-filters">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label for="search_pre_province"><span class="merger-step" aria-hidden="true">1</span>Tỉnh/thành trước sáp nhập</label><input id="search_pre_province" type="search" class="form-control" placeholder="Chọn hoặc gõ tên tỉnh..." autocomplete="off"></div>
                        <div class="form-group col-md-4"><label for="search_pre_district"><span class="merger-step" aria-hidden="true">2</span>Quận/huyện</label><input id="search_pre_district" type="search" class="form-control" placeholder="Chọn tỉnh trước" autocomplete="off" disabled></div>
                        <div class="form-group col-md-4"><label for="search_pre_ward"><span class="merger-step" aria-hidden="true">3</span>Phường/xã</label><input id="search_pre_ward" type="search" class="form-control" placeholder="Chọn huyện trước" autocomplete="off" disabled></div>
                    </div>
                    <p class="merger-filter-help"><i class="fas fa-search mr-2" aria-hidden="true"></i>Gõ tên có dấu hoặc không dấu, rồi chọn địa bàn trong danh sách.</p>
                </div>
                <div id="pre_status" role="status" aria-live="polite" class="merger-status"></div>
                <div class="merger-empty" id="pre_empty"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><div><strong>Bắt đầu từ địa chỉ cũ</strong><span>Chọn tỉnh/thành, quận/huyện và xã/phường để xem địa bàn sau sáp nhập.</span></div></div>
                <section id="pre_results" hidden>
                    <h2 class="merger-results-title"><i class="fas fa-map-pin mr-2" aria-hidden="true"></i>Kết quả sau sáp nhập</h2>
                    <div class="row"><div class="col-lg-4" id="pre_province_results"></div><div class="col-lg-8"><div class="merger-empty merger-ward-hint" id="pre_ward_hint">Chọn tiếp quận/huyện và xã/phường để xem kết quả chi tiết.</div><div id="pre_wards_results"></div></div></div>
                </section>
            </div>
            <div class="tab-pane fade" id="post" role="tabpanel" aria-labelledby="post-tab">
                <div class="merger-filters">
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="search_post_province"><span class="merger-step" aria-hidden="true">1</span>Tỉnh/thành sau sáp nhập</label><input id="search_post_province" type="search" class="form-control" placeholder="Chọn hoặc gõ tên tỉnh..." autocomplete="off"></div>
                        <div class="form-group col-md-6"><label for="search_post_ward"><span class="merger-step" aria-hidden="true">2</span>Phường/xã</label><input id="search_post_ward" type="search" class="form-control" placeholder="Chọn tỉnh trước" autocomplete="off" disabled></div>
                    </div>
                    <p class="merger-filter-help"><i class="fas fa-search mr-2" aria-hidden="true"></i>Chọn địa bàn mới để xem các xã/phường cũ được sáp nhập vào.</p>
                </div>
                <div id="post_status" role="status" aria-live="polite" class="merger-status"></div>
                <div class="merger-empty" id="post_empty"><i class="fas fa-history" aria-hidden="true"></i><div><strong>Tìm lại địa bàn trước sáp nhập</strong><span>Chọn tỉnh/thành và xã/phường mới để xem các đơn vị cũ tương ứng.</span></div></div>
                <div id="post_results"></div>
            </div>
        </div>
        <p class="merger-source-note"><i class="fas fa-info-circle mr-2" aria-hidden="true"></i>Dữ liệu theo mốc {{ \Carbon\Carbon::parse($dataset->snapshot_date)->format('d/m/Y') }} từ các file nguồn; chưa xác minh toàn bộ thay đổi sau mốc này.</p>
    @endif
</div>
@endsection
@push('scripts')
@include('shared.jquery-ui')
<script src="{{ asset('js/merger-lookup.js') }}?v={{ filemtime(public_path('js/merger-lookup.js')) }}"></script>
@endpush
