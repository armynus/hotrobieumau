@extends('admin.layouts.app')

@section('title', 'Nhập dữ liệu sáp nhập địa danh')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Nhập dữ liệu sáp nhập địa danh</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tải lên file Excel</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin_merger_data_import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">Chọn file Excel (.xlsx, .xls, .csv)</label>
                    <input type="file" class="form-control-file" id="file" name="file" required accept=".xlsx, .xls, .csv">
                </div>
                <p class="small text-muted">
                    File cần có dòng tiêu đề (hàng đầu tiên) với các cột: <strong>tinh_cu, huyen_cu, xa_cu, tinh_moi, xa_moi</strong>
                </p>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> Bắt đầu nhập</button>
            </form>
        </div>
    </div>
</div>
@endsection
