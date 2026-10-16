@extends('admin.layouts.app')

@section('title', 'Danh mục Mẫu biểu IT')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Danh mục Mẫu biểu IT</h1>
            <p class="mb-0 text-muted small">Quản lý các danh mục phân loại và mô tả phạm vi hệ thống áp dụng cho Mẫu yêu cầu hỗ trợ IT.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addCategoryModal">
            <i class="fas fa-plus mr-1"></i> Thêm danh mục mới
        </button>
    </div>
    
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-folder-open mr-1"></i> Danh sách Danh mục Mẫu biểu IT ({{ $categories->count() }})
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="itCategoryTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">Thứ tự</th>
                            <th style="width: 250px;">Tên danh mục</th>
                            <th>Mô tả phạm vi hỗ trợ & Hệ thống áp dụng</th>
                            <th style="width: 100px;" class="text-center">Số mẫu</th>
                            <th style="width: 110px;" class="text-center">Trạng thái</th>
                            <th style="width: 130px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td class="text-center font-weight-bold text-muted">{{ $category->display_order }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-2 text-primary" style="width: 34px; height: 34px;">
                                            <i class="{{ $category->icon ?: 'fas fa-folder' }}"></i>
                                        </div>
                                        <div>
                                            <span class="font-weight-bold text-gray-800">{{ $category->name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($category->description)
                                        <div class="text-dark small" style="line-height: 1.5;">
                                            {{ $category->description }}
                                        </div>
                                    @else
                                        <span class="text-muted font-italic small">Chưa có mô tả phạm vi</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-pill badge-primary px-2 py-1 font-weight-bold">
                                        {{ $category->templates_count ?? 0 }} mẫu
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($category->status === 'active')
                                        <span class="badge badge-success px-2 py-1">Hoạt động</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">Tạm khóa</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-info btn-circle btn-edit-category" 
                                            title="Sửa danh mục"
                                            data-id="{{ $category->id }}"
                                            data-name="{{ $category->name }}"
                                            data-description="{{ $category->description }}"
                                            data-icon="{{ $category->icon }}"
                                            data-order="{{ $category->display_order }}"
                                            data-status="{{ $category->status }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.it_support.categories.destroy', $category->id) }}" method="POST" class="d-inline-block form-delete-category">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger btn-circle btn-delete-trigger" title="Xóa danh mục">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Chưa có danh mục nào. Bấm "Thêm danh mục mới" để bắt đầu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Danh mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Thêm Danh mục Mẫu biểu IT
                </h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.it_support.categories.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Tên danh mục <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Ví dụ: Hệ thống IPCAS, Các phần mềm nghiệp vụ, Chứng thư số PKI..." required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Thứ tự hiển thị</label>
                                <input type="number" name="display_order" class="form-control" value="10" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold d-flex justify-content-between align-items-center">
                                    <span>Biểu tượng (FontAwesome icon)</span>
                                    <span class="badge badge-light border text-primary px-2 py-1" id="add_icon_preview"><i class="fas fa-university"></i></span>
                                </label>
                                <select name="icon" class="form-control select-icon-picker">
                                    <optgroup label="🏦 Hệ thống Ngân hàng & Nghiệp vụ">
                                        <option value="fas fa-university" selected>🏦 fas fa-university (Hệ thống IPCAS / Core Banking)</option>
                                        <option value="fas fa-cubes">🧩 fas fa-cubes (Các phần mềm nghiệp vụ)</option>
                                        <option value="fas fa-calculator">📊 fas fa-calculator (Kế toán Fast / MISA)</option>
                                        <option value="fas fa-laptop-code">💻 fas fa-laptop-code (Phần mềm / Ứng dụng nghiệp vụ)</option>
                                        <option value="fas fa-money-check-alt">💳 fas fa-money-check-alt (Giao dịch / Chuyển tiền / POS)</option>
                                        <option value="fas fa-coins">🪙 fas fa-coins (Tiền tệ / Quản lý kho quỹ)</option>
                                    </optgroup>
                                    <optgroup label="🔑 Chứng thư số & Bảo mật & Phân quyền">
                                        <option value="fas fa-key">🔑 fas fa-key (Chứng thư số PKI / USB Token)</option>
                                        <option value="fas fa-certificate">📜 fas fa-certificate (Chứng chỉ số / Chữ ký số)</option>
                                        <option value="fas fa-shield-alt">🛡️ fas fa-shield-alt (Bảo mật / Chữ ký số điện tử)</option>
                                        <option value="fas fa-user-shield">👤 fas fa-user-shield (Cấp quyền IPCAS / Active Directory)</option>
                                        <option value="fas fa-id-badge">🪪 fas fa-id-badge (Tài khoản / Định danh cán bộ)</option>
                                        <option value="fas fa-lock">🔒 fas fa-lock (Khóa / Mở khóa tài khoản)</option>
                                    </optgroup>
                                    <optgroup label="⚙️ Quản trị hệ thống & BeAdmin">
                                        <option value="fas fa-user-cog">⚙️ fas fa-user-cog (Các chương trình BeAdmin / Quản trị viên)</option>
                                        <option value="fas fa-users-cog">👥 fas fa-users-cog (Quản trị nhóm người dùng / Phân hệ)</option>
                                        <option value="fas fa-tools">🛠️ fas fa-tools (Công cụ kỹ thuật / Tiện ích hệ thống)</option>
                                        <option value="fas fa-sliders-h">🎛️ fas fa-sliders-h (Cấu hình tham số hệ thống)</option>
                                    </optgroup>
                                    <optgroup label="🖥️ Thiết bị, Mạng & Hạ tầng">
                                        <option value="fas fa-desktop">🖥️ fas fa-desktop (Máy tính trạm / Thiết bị quầy)</option>
                                        <option value="fas fa-print">🖨️ fas fa-print (Máy in / Máy scan / Phần cứng)</option>
                                        <option value="fas fa-network-wired">🌐 fas fa-network-wired (Mạng LAN/WAN / Đường truyền)</option>
                                        <option value="fas fa-server">🏢 fas fa-server (Máy chủ / Hạ tầng CNTT)</option>
                                        <option value="fas fa-database">🗄️ fas fa-database (Cơ sở dữ liệu / Sao lưu)</option>
                                        <option value="fas fa-wifi">📡 fas fa-wifi (Wifi / Kết nối không dây)</option>
                                    </optgroup>
                                    <optgroup label="🎧 Hỗ trợ khác">
                                        <option value="fas fa-headset">🎧 fas fa-headset (Yêu cầu hỗ trợ khác / Hotline IT)</option>
                                        <option value="fas fa-envelope">✉️ fas fa-envelope (Hệ thống Email Agribank)</option>
                                        <option value="fas fa-life-ring">🛟 fas fa-life-ring (Hỗ trợ khẩn cấp / Xử lý sự cố)</option>
                                        <option value="fas fa-folder">📁 fas fa-folder (Thư mục / Danh mục chung)</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Trạng thái</label>
                                <select name="status" class="form-control">
                                    <option value="active" selected>Hoạt động (Hiển thị cho người dùng)</option>
                                    <option value="inactive">Tạm ẩn</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">
                            Mô tả phạm vi hỗ trợ & Hệ thống áp dụng 
                            <span class="text-info font-weight-normal">(Hiển thị hướng dẫn cho người dùng tại chi nhánh)</span>
                        </label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Ví dụ: Dành cho các lỗi liên quan đến Core Banking (IPCAS), phần mềm kế toán Fast/MISA, chứng thư số PKI, BeAdmin..."></textarea>
                        <small class="form-text text-muted">
                            Mô tả chi tiết các phần mềm, nghiệp vụ áp dụng giúp cán bộ chi nhánh chọn chính xác biểu mẫu ngay từ trang tra cứu.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy bỏ</button>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-save mr-1"></i> Lưu danh mục
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa Danh mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-edit mr-1"></i> Cập nhật Danh mục Mẫu biểu IT
                </h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editCategoryForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">Tên danh mục <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Thứ tự hiển thị</label>
                                <input type="number" name="display_order" id="edit_display_order" class="form-control" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold d-flex justify-content-between align-items-center">
                                    <span>Biểu tượng (FontAwesome icon)</span>
                                    <span class="badge badge-light border text-info px-2 py-1" id="edit_icon_preview"><i class="fas fa-folder"></i></span>
                                </label>
                                <select name="icon" id="edit_icon" class="form-control select-icon-picker">
                                    <optgroup label="🏦 Hệ thống Ngân hàng & Nghiệp vụ">
                                        <option value="fas fa-university">🏦 fas fa-university (Hệ thống IPCAS / Core Banking)</option>
                                        <option value="fas fa-cubes">🧩 fas fa-cubes (Các phần mềm nghiệp vụ)</option>
                                        <option value="fas fa-calculator">📊 fas fa-calculator (Kế toán Fast / MISA)</option>
                                        <option value="fas fa-laptop-code">💻 fas fa-laptop-code (Phần mềm / Ứng dụng nghiệp vụ)</option>
                                        <option value="fas fa-money-check-alt">💳 fas fa-money-check-alt (Giao dịch / Chuyển tiền / POS)</option>
                                        <option value="fas fa-coins">🪙 fas fa-coins (Tiền tệ / Quản lý kho quỹ)</option>
                                    </optgroup>
                                    <optgroup label="🔑 Chứng thư số & Bảo mật & Phân quyền">
                                        <option value="fas fa-key">🔑 fas fa-key (Chứng thư số PKI / USB Token)</option>
                                        <option value="fas fa-certificate">📜 fas fa-certificate (Chứng chỉ số / Chữ ký số)</option>
                                        <option value="fas fa-shield-alt">🛡️ fas fa-shield-alt (Bảo mật / Chữ ký số điện tử)</option>
                                        <option value="fas fa-user-shield">👤 fas fa-user-shield (Cấp quyền IPCAS / Active Directory)</option>
                                        <option value="fas fa-id-badge">🪪 fas fa-id-badge (Tài khoản / Định danh cán bộ)</option>
                                        <option value="fas fa-lock">🔒 fas fa-lock (Khóa / Mở khóa tài khoản)</option>
                                    </optgroup>
                                    <optgroup label="⚙️ Quản trị hệ thống & BeAdmin">
                                        <option value="fas fa-user-cog">⚙️ fas fa-user-cog (Các chương trình BeAdmin / Quản trị viên)</option>
                                        <option value="fas fa-users-cog">👥 fas fa-users-cog (Quản trị nhóm người dùng / Phân hệ)</option>
                                        <option value="fas fa-tools">🛠️ fas fa-tools (Công cụ kỹ thuật / Tiện ích hệ thống)</option>
                                        <option value="fas fa-sliders-h">🎛️ fas fa-sliders-h (Cấu hình tham số hệ thống)</option>
                                    </optgroup>
                                    <optgroup label="🖥️ Thiết bị, Mạng & Hạ tầng">
                                        <option value="fas fa-desktop">🖥️ fas fa-desktop (Máy tính trạm / Thiết bị quầy)</option>
                                        <option value="fas fa-print">🖨️ fas fa-print (Máy in / Máy scan / Phần cứng)</option>
                                        <option value="fas fa-network-wired">🌐 fas fa-network-wired (Mạng LAN/WAN / Đường truyền)</option>
                                        <option value="fas fa-server">🏢 fas fa-server (Máy chủ / Hạ tầng CNTT)</option>
                                        <option value="fas fa-database">🗄️ fas fa-database (Cơ sở dữ liệu / Sao lưu)</option>
                                        <option value="fas fa-wifi">📡 fas fa-wifi (Wifi / Kết nối không dây)</option>
                                    </optgroup>
                                    <optgroup label="🎧 Hỗ trợ khác">
                                        <option value="fas fa-headset">🎧 fas fa-headset (Yêu cầu hỗ trợ khác / Hotline IT)</option>
                                        <option value="fas fa-envelope">✉️ fas fa-envelope (Hệ thống Email Agribank)</option>
                                        <option value="fas fa-life-ring">🛟 fas fa-life-ring (Hỗ trợ khẩn cấp / Xử lý sự cố)</option>
                                        <option value="fas fa-folder">📁 fas fa-folder (Thư mục / Danh mục chung)</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Trạng thái</label>
                                <select name="status" id="edit_status" class="form-control">
                                    <option value="active">Hoạt động (Hiển thị cho người dùng)</option>
                                    <option value="inactive">Tạm ẩn</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">
                            Mô tả phạm vi hỗ trợ & Hệ thống áp dụng 
                            <span class="text-info font-weight-normal">(Hiển thị hướng dẫn cho người dùng tại chi nhánh)</span>
                        </label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        <small class="form-text text-muted">
                            Mô tả chi tiết các phần mềm, nghiệp vụ áp dụng giúp cán bộ chi nhánh chọn chính xác biểu mẫu ngay từ trang tra cứu.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy bỏ</button>
                    <button class="btn btn-info" type="submit">
                        <i class="fas fa-save mr-1"></i> Cập nhật thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
    if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#itCategoryTable')) {
        $('#itCategoryTable').DataTable({
            language: {
                processing: "Đang tải...",
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                infoEmpty: "Không có dữ liệu",
                zeroRecords: "Không tìm thấy dữ liệu",
                paginate: {
                    first: "Đầu",
                    last: "Cuối",
                    next: "Sau",
                    previous: "Trước"
                }
            }
        });
    }
    // Sửa danh mục
    $('.btn-edit-category').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        const icon = $(this).data('icon');
        const order = $(this).data('order');
        const status = $(this).data('status');

        const updateUrl = '{{ route("admin.it_support.categories.update", ":id") }}'.replace(':id', id);
        $('#editCategoryForm').attr('action', updateUrl);

        $('#edit_name').val(name);
        $('#edit_description').val(description || '');
        $('#edit_icon').val(icon || 'fas fa-folder');
        $('#edit_icon_preview i').attr('class', icon || 'fas fa-folder');
        $('#edit_display_order').val(order || 10);
        $('#edit_status').val(status || 'active');

        $('#editCategoryModal').modal('show');
    });

    // Cập nhật live preview biểu tượng khi thay đổi lựa chọn trong dropdown
    $(document).on('change', '.select-icon-picker', function() {
        const iconClass = $(this).val() || 'fas fa-folder';
        $(this).closest('.form-group').find('label span.badge i').attr('class', iconClass);
    });

    // Xác nhận xóa
    $('.btn-delete-trigger').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        if (confirm('Bạn có chắc chắn muốn xóa danh mục này? Các biểu mẫu thuộc danh mục sẽ không bị mất mà chỉ được gỡ phân loại.')) {
            form.submit();
        }
    });
});
</script>
@endpush
