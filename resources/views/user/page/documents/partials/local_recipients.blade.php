<div class="row local-recipients">
    <div class="col-md-6 mb-3">
        <div class="border rounded p-3 h-100 bg-light">
            <div class="font-weight-bold text-primary mb-2"><i class="fas fa-user-tie mr-1"></i> Ban giám đốc chi nhánh mình</div>
            @if($directors->isNotEmpty())
                <label class="d-flex align-items-center border-bottom pb-2"><input type="checkbox" class="mr-2" onchange="this.closest('div').querySelectorAll('[name=&quot;to_user_ids[]&quot;]').forEach(input => input.checked = this.checked)">Chọn tất cả ban giám đốc</label>
            @endif
            <div style="max-height:220px;overflow-y:auto;">
                @forelse($directors as $director)
                    <label class="d-flex align-items-start mb-2"><input type="checkbox" name="to_user_ids[]" value="{{ $director->id }}" class="mr-2 mt-1"><span>{{ $director->name }}<small class="d-block text-muted">{{ $director->position?->position_name }}</small></span></label>
                @empty
                    <small class="text-muted">Chưa có user được gán chức vụ Giám đốc / Phó Giám đốc trong chi nhánh này. Vào Quản lý người dùng → Chỉnh sửa → chọn <strong>Chức Vụ Thực Tế</strong> và đúng chi nhánh rồi lưu. Chỉ đặt tên user hoặc chọn phòng Ban Giám Đốc chưa thay cho chức vụ.</small>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="border rounded p-3 h-100 bg-light">
            <div class="font-weight-bold text-primary mb-2"><i class="fas fa-sitemap mr-1"></i> Phòng ban chi nhánh mình</div>
            @if($departments->isNotEmpty())
                <label class="d-flex align-items-center border-bottom pb-2"><input type="checkbox" class="mr-2" onchange="this.closest('div').querySelectorAll('[name=&quot;to_department_ids[]&quot;]').forEach(input => input.checked = this.checked)">Chọn tất cả phòng ban</label>
            @endif
            <div style="max-height:220px;overflow-y:auto;">
                @forelse($departments as $department)
                    <label class="d-flex align-items-start mb-2"><input type="checkbox" name="to_department_ids[]" value="{{ $department->id }}" class="mr-2 mt-1"><span>{{ $department->department_name }}</span></label>
                @empty
                    <small class="text-muted">Chưa có phòng ban hoạt động.</small>
                @endforelse
            </div>
        </div>
    </div>
</div>
