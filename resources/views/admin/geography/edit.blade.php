@extends('admin.layouts.app')
@section('title', 'Sửa dữ liệu xã/phường')
@section('content')
<div class="container py-4">
    <a href="{{ route('admin.geography.index') }}">← Dữ liệu xã/phường</a>
    <h1 class="h3 my-3">Sửa liên kết cũ–mới</h1>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <form class="card card-body" method="POST" action="{{ route('admin.geography.update', $row->id) }}">
        @csrf<input type="hidden" name="revision" value="{{ $row->revision }}">
        <p class="alert alert-light border">Đơn vị mới: <strong>{{ $row->new_name }}</strong> · {{ $row->new_province }} · Mã {{ $row->new_code }}</p>
        <div class="form-row">
            @foreach(['old_province'=>'Tỉnh/thành cũ','old_district'=>'Quận/huyện cũ','old_name'=>'Xã/phường cũ'] as $field=>$label)
            <div class="form-group col-md-4"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" maxlength="255" value="{{ old($field, $row->$field) }}" @if($field !== 'old_name') required @endif></div>
            @endforeach
        </div>
        <div class="form-row">
            <div class="form-group col-md-6"><label for="scope">Phạm vi</label><select class="form-control" name="scope" id="scope">@foreach([''=>'Theo mô tả nguồn','whole'=>'Toàn bộ','part'=>'Một phần','remainder'=>'Phần còn lại'] as $key=>$label)<option value="{{ $key }}" @selected(old('scope', $row->scope) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group col-md-6"><label for="enabled">Sử dụng</label><select class="form-control" name="enabled" id="enabled"><option value="1" @selected(old('enabled', $row->enabled) == 1)>Hiển thị trong tra cứu</option><option value="0" @selected(old('enabled', $row->enabled) == 0)>Loại khỏi tra cứu</option></select></div>
        </div>
        <div class="form-group"><label for="note">Ghi chú</label><textarea class="form-control" name="note" id="note" rows="3" maxlength="10000">{{ old('note', $row->note) }}</textarea></div>
        <div><button class="btn btn-primary">Lưu thay đổi</button></div>
    </form>
</div>
@endsection
