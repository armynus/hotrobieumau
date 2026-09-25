@extends('admin.layouts.app')
@section('title', 'Dữ liệu xã/phường')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-2">Dữ liệu xã/phường</h1>
        @if($dataset)<a class="btn btn-outline-primary" href="{{ route('admin.geography.notes') }}"><i class="fas fa-download"></i> Tải ghi chú đối chiếu</a>@endif
    </div>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @if(!$ready)
        <div class="alert alert-info">Chưa cài đặt bảng dữ liệu. Chạy migration <code>2026_09_25_140000_create_geography_catalog_tables.php</code> trước khi nhập.</div>
    @else
        <details class="card shadow-sm mb-4" @if(!$dataset) open @endif>
            <summary class="card-header text-primary font-weight-bold p-3" style="cursor:pointer"><i class="fas fa-file-import mr-2"></i>Nhập / cập nhật dữ liệu</summary>
            <form class="card-body" method="POST" action="{{ route('admin.geography.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="geoSource">Nguồn nhập</label><select id="geoSource" name="source" class="form-control"><option value="prepared">Bộ toàn quốc đã tổng hợp từ 3 file Excel</option><option value="file" @selected(old('source') === 'file')>Tải file Excel / JSON khác</option></select></div>
                    <div class="form-group col-md-6"><label for="geoFile">File dữ liệu (khi chọn tải file khác)</label><input id="geoFile" name="file" type="file" class="form-control-file" accept=".json,.xlsx,.xls,.csv"></div>
                </div>
                <p class="small text-muted">Excel nhận bảng liên kết cũ–mới; bộ có sẵn đã kết hợp cả 3 file và loại liên kết sai đã xác định. <a href="{{ asset('templates/xa-phuong.csv') }}" download>Tải mẫu cột nhập CSV</a>.</p>
                <label class="d-block"><input type="checkbox" name="replace" value="1" required> Dùng bộ vừa nhập thay bộ tra cứu hiện tại (bản nguồn trước vẫn được giữ).</label>
                <button class="btn btn-primary" type="submit"><i class="fas fa-upload mr-1"></i> Nhập dữ liệu</button>
            </form>
        </details>
        @if($dataset)
            <div class="mb-3 text-dark"><strong>{{ number_format($stats['provinces']) }} tỉnh/thành</strong> · {{ number_format($stats['wards']) }} xã/phường · {{ number_format($stats['links']) }} liên kết · {{ $stats['excluded'] }} dòng đã loại</div>
            <p class="small text-muted">Mốc dữ liệu {{ \Carbon\Carbon::parse($dataset->snapshot_date)->format('d/m/Y') }}. {{ count($notes) }} ghi chú đối chiếu. <a href="{{ route('admin.geography.source') }}">Tải bản nguồn</a></p>
            <div class="card shadow-sm"><div class="card-body">
                <form method="GET" class="form-row align-items-end mb-3">
                    <div class="form-group col-md-5"><label for="geoQ">Tìm tên hoặc mã xã</label><input id="geoQ" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Tên xã, huyện, tỉnh hoặc mã mới"></div>
                    <div class="form-group col-md-3"><label for="geoProvince">Tỉnh/thành mới</label><select id="geoProvince" name="province" class="form-control"><option value="">Tất cả</option>@foreach($provinces as $province)<option @selected(($filters['province'] ?? '') === $province)>{{ $province }}</option>@endforeach</select></div>
                    <div class="form-group col-md-2"><label for="geoStatus">Hiển thị</label><select id="geoStatus" name="status" class="form-control">@foreach(['all'=>'Tất cả','enabled'=>'Đang dùng','excluded'=>'Đã loại','noted'=>'Có ghi chú'] as $key=>$label)<option value="{{ $key }}" @selected(($filters['status'] ?? 'all') === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-group col-md-2"><button class="btn btn-primary w-100">Lọc dữ liệu</button></div>
                </form>
                <div class="table-responsive"><table class="table table-bordered table-hover">
                    <thead><tr><th>Địa bàn cũ</th><th>Xã/phường mới</th><th>Phạm vi / Ghi chú</th><th></th></tr></thead>
                    <tbody>@forelse($rows as $row)<tr @if(!$row->enabled) class="table-warning" @endif>
                        <td>{{ $row->old_name ?: '(Đơn vị cấp huyện)' }}<small class="d-block text-muted">{{ $row->old_district }} · {{ $row->old_province }}</small></td>
                        <td><strong>{{ $row->new_name }}</strong><small class="d-block">{{ $row->new_province }} · {{ $row->new_code }}</small></td>
                        <td>{{ \App\Services\Geography\GeographyStore::scope($row->scope) ?: $row->source_relation }}@if(!$row->enabled)<span class="badge badge-warning ml-1">Đã loại</span>@endif
                            @if($row->note)<small class="d-block">{{ $row->note }}</small>@endif
                        </td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.geography.edit', $row->id) }}">Sửa</a></td>
                    </tr>@empty<tr><td colspan="4" class="text-center">Không có dòng phù hợp.</td></tr>@endforelse</tbody>
                </table></div>
                {{ $rows->links('pagination::bootstrap-4') }}
            </div></div>
        @endif
    @endif
</div>
@endsection
