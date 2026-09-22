<script>
$(function () {
    function setParentVisibility(prefix) {
        var type = $('#' + prefix + 'branch_type').val();
        var $group = $('#' + prefix + 'branch_parent_group');
        $group.toggle(type === 'type_2');
        if (type !== 'type_2') $('#' + prefix + 'branch_parent_id').val('');
    }

    function branchPayload(prefix) {
        return {
            _token: @json(csrf_token()),
            branch_name: $('#' + prefix + 'branch_name').val(),
            branch_code: $('#' + prefix + 'branch_code').val(),
            branch_type: $('#' + prefix + 'branch_type').val(),
            parent_id: $('#' + prefix + 'branch_parent_id').val(),
            branch_addr: $('#' + prefix + 'branch_addr').val(),
            branch_phone: $('#' + prefix + 'branch_phone').val(),
            branch_fax: $('#' + prefix + 'branch_fax').val(),
            branch_place: $('#' + prefix + 'branch_place').val(),
            branch_tax_code: $('#' + prefix + 'branch_tax_code').val(),
            branch_tax_date: $('#' + prefix + 'branch_tax_date').val(),
            branch_tax_place: $('#' + prefix + 'branch_tax_place').val(),
            branch_general: $('#' + prefix + 'branch_general').val()
        };
    }

    function showBranchError(xhr, fallback) {
        var response = xhr.responseJSON || {};
        var errors = response.errors || {};
        var firstError = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : null;
        swal(firstError || response.message || fallback, {icon: 'error'});
    }

    $('#branch_type').on('change', function () { setParentVisibility(''); });
    $('#edit_branch_type').on('change', function () { setParentVisibility('edit_'); });
    $('#addBranchModal').on('show.bs.modal', function () { setParentVisibility(''); });

    $('#addBranch').on('click', function () {
        var $button = $(this).prop('disabled', true);
        $.ajax({
            url: @json(route('admin_branches_store')),
            method: 'POST',
            data: branchPayload(''),
            success: function (response) {
                swal('Thành công!', response.message, {icon: 'success'}).then(function () { location.reload(); });
            },
            error: function (xhr) { showBranchError(xhr, 'Không thể tạo chi nhánh.'); },
            complete: function () { $button.prop('disabled', false); }
        });
    });

    $(document).on('click', '.edit_branch', function () {
        $.ajax({
            url: @json(route('admin_branches_edit')),
            method: 'GET',
            data: {branch_id: $(this).data('branch_id')},
            success: function (response) {
                var branch = response.branch;
                $('#edit_branch_id').val(branch.id);
                $('#edit_branch_name').val(branch.branch_name || '');
                $('#edit_branch_code').val(branch.branch_code || '');
                $('#edit_branch_type').val(branch.branch_type || 'type_2');
                $('#edit_branch_parent_id').val(branch.parent_id || '');
                $('#edit_branch_addr').val(branch.branch_addr || '');
                $('#edit_branch_phone').val(branch.branch_phone || '');
                $('#edit_branch_fax').val(branch.branch_fax || '');
                $('#edit_branch_place').val(branch.branch_place || '');
                $('#edit_branch_tax_code').val(branch.branch_tax_code || '');
                $('#edit_branch_tax_date').val(branch.branch_tax_date || '');
                $('#edit_branch_tax_place').val(branch.branch_tax_place || '');
                $('#edit_branch_general').val(branch.branch_general || '');
                setParentVisibility('edit_');
            },
            error: function (xhr) { showBranchError(xhr, 'Không thể tải thông tin chi nhánh.'); }
        });
    });

    $('#updateBranchButton').on('click', function () {
        var payload = branchPayload('edit_');
        payload.branch_id = $('#edit_branch_id').val();
        var $button = $(this).prop('disabled', true);
        $.ajax({
            url: @json(route('admin_branches_update')),
            method: 'POST',
            data: payload,
            success: function (response) {
                swal('Thành công!', response.message, {icon: 'success'}).then(function () { location.reload(); });
            },
            error: function (xhr) { showBranchError(xhr, 'Không thể cập nhật chi nhánh.'); },
            complete: function () { $button.prop('disabled', false); }
        });
    });

    $(document).on('click', '.lock_branch, .unlock_branch', function () {
        var branchId = $(this).data('branch_id');
        var unlocking = $(this).hasClass('unlock_branch');
        swal({
            title: unlocking ? 'Mở khóa chi nhánh?' : 'Khóa chi nhánh?',
            text: unlocking ? 'Nhân viên của chi nhánh sẽ đăng nhập lại được.' : 'Nhân viên của chi nhánh sẽ không thể đăng nhập.',
            icon: 'warning',
            buttons: true,
            dangerMode: !unlocking
        }).then(function (confirmed) {
            if (!confirmed) return;
            $.ajax({
                url: @json(route('admin_branches_lock')),
                method: 'POST',
                data: {_token: @json(csrf_token()), branch_id: branchId},
                success: function (response) {
                    swal('Thành công!', response.message, {icon: 'success'}).then(function () { location.reload(); });
                },
                error: function (xhr) { showBranchError(xhr, 'Không thể đổi trạng thái chi nhánh.'); }
            });
        });
    });
});
</script>
