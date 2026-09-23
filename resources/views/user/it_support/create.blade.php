@extends('user.layouts.app')
@section('title', 'Gửi yêu cầu hỗ trợ IT')
@push('styles')<link rel="stylesheet" href="{{ asset('css/it-support.css') }}?v={{ filemtime(public_path('css/it-support.css')) }}">@endpush
@section('content')
<div class="container-fluid it-page">
    <header class="it-hero"><div><p class="it-eyebrow">PHIẾU HỖ TRỢ MỚI</p><h1>Gửi yêu cầu hỗ trợ IT</h1><p>Mô tả rõ tình huống để IT nắm vấn đề và xử lý nhanh hơn.</p></div><a href="{{ route('user.it_support.index') }}" class="btn it-light"><i class="fas fa-arrow-left" aria-hidden="true"></i> Danh sách phiếu</a></header>
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Chưa gửi được phiếu.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="it-form-layout"><form class="it-panel" action="{{ route('user.it_support.store') }}" method="POST" enctype="multipart/form-data">@csrf
        <h2>Thông tin cần hỗ trợ</h2><p class="it-muted">Các mục có dấu * cần được điền.</p>
        <div class="form-group"><label for="title">Tiêu đề <span class="text-danger">*</span></label><input class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="255" required placeholder="Ví dụ: Máy in tại quầy giao dịch không kết nối"></div>
        <div class="form-group"><label for="description">Mô tả sự cố <span class="text-danger">*</span></label><textarea class="form-control" id="description" name="description" rows="6" minlength="10" maxlength="5000" required placeholder="Thiết bị hoặc ứng dụng nào gặp vấn đề? Lỗi xuất hiện từ khi nào? Bạn đã thử thao tác gì?">{{ old('description') }}</textarea><small class="form-text text-muted">Không ghi mật khẩu hoặc thông tin khách hàng nhạy cảm vào phiếu.</small></div>
        <div class="it-two"><div class="form-group"><label for="category">Nhóm hỗ trợ</label><select class="form-control" id="category" name="category"><option value="">Chọn nhóm (không bắt buộc)</option>@foreach(['Phần cứng','Phần mềm','Mạng','Khác'] as $item)<option value="{{ $item }}" @selected(old('category') === $item)>{{ $item }}</option>@endforeach</select></div><div class="form-group"><label for="contact_phone">Số điện thoại liên hệ</label><input class="form-control" id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="20" inputmode="tel" placeholder="Để IT gọi khi cần"></div></div>
        <div class="form-group it-upload-field">
            <p class="it-upload-label" id="attachmentsLabel">Ảnh chụp lỗi hoặc tài liệu đính kèm</p>
            <div class="it-upload-zone" id="itUploadZone">
                <input class="it-upload-input" type="file" id="attachments" name="attachments[]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple aria-labelledby="attachmentsLabel" aria-describedby="fileHelp fileError">
                <label class="it-upload-target" for="attachments">
                    <span class="it-upload-icon"><i class="fas fa-cloud-upload-alt" aria-hidden="true"></i></span>
                    <span class="it-upload-title">Kéo thả tệp vào đây</span>
                    <span class="it-upload-subtitle">hoặc chọn ảnh và tài liệu từ máy của bạn</span>
                    <span class="it-upload-button"><i class="fas fa-plus" aria-hidden="true"></i> Chọn tệp</span>
                </label>
            </div>
            <div class="it-upload-footer"><small id="fileHelp">JPG, PNG, PDF, Word · Tối đa 5 tệp, 20 MB/tệp. Chỉ bạn và bộ phận quản trị được tải tệp.</small><span id="fileCount" aria-live="polite">0/5 tệp</span></div>
            <p id="fileError" class="it-upload-error" role="alert" hidden></p>
            <ul id="fileSelection" class="it-upload-list" aria-label="Tệp đã chọn" hidden></ul>
        </div>
        <div class="it-form-actions"><button type="submit" class="btn it-primary"><i class="fas fa-paper-plane" aria-hidden="true"></i> Gửi yêu cầu</button><a href="{{ route('user.it_support.index') }}" class="btn it-light">Hủy</a></div>
    </form><aside class="it-panel it-help"><div class="it-help-icon"><i class="fas fa-lightbulb" aria-hidden="true"></i></div><h2>Để IT hỗ trợ nhanh hơn</h2><ol><li>Ghi tên thiết bị, ứng dụng hoặc vị trí gặp lỗi.</li><li>Mô tả thông báo lỗi và thời điểm xảy ra.</li><li>Đính kèm ảnh màn hình nếu có.</li></ol><p>Phiếu được lưu ngay khi gửi. Sau khi hoàn tất, hồ sơ được lưu theo ngày hoàn thiện để tiện đối chiếu.</p></aside></div>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/user/it-support-upload.js') }}?v={{ filemtime(public_path('js/user/it-support-upload.js')) }}"></script>@endpush
