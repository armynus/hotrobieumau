/**
 * Admin Form Types & Sub Form Types Manager
 */
(function (window, $) {
    'use strict';

    function initFormTypeManager() {
        window.AdminFormTypeConfig = window.AdminFormTypeConfig || {};
        $('.admin-form-type-config-data').each(function () {
            try {
                var data = JSON.parse(this.dataset.config || '{}');
                if (data.routes) {
                    window.AdminFormTypeConfig.routes = Object.assign(window.AdminFormTypeConfig.routes || {}, data.routes);
                }
                delete data.routes;
                Object.assign(window.AdminFormTypeConfig, data);
            } catch (e) {}
        });
        var cfg = window.AdminFormTypeConfig;
        var routes = cfg.routes || {};

        // ==========================================
        // 1. MAIN FORM TYPE (Loại biểu mẫu)
        // ==========================================

        $(document).on('click', '#updateFormType', function (e) {
            e.preventDefault();
            var formTypeId = $('#view_id').val();
            var type_name = $('#edit_type_name').val();

            var updateUrl = routes.formTypeUpdate || '/admin/form-type/update';

            $.ajax({
                type: 'POST',
                url: updateUrl,
                data: {
                    id: formTypeId,
                    type_name: type_name
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof swal === 'function') {
                            swal({ icon: 'success', title: 'Thành công', text: response.message });
                        }

                        var updatedFormType = response.data;
                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#FormTypeTable')) {
                            var table = $('#FormTypeTable').DataTable();
                            var rowIndex = table.rows().eq(0).filter(function (index) {
                                return table.cell(index, 0).data() == updatedFormType.id;
                            });

                            if (rowIndex.length) {
                                table.row(rowIndex).data({
                                    id: updatedFormType.id,
                                    type_name: updatedFormType.type_name,
                                    created_at: new Date(updatedFormType.created_at).toLocaleString('vi-VN'),
                                    updated_at: new Date(updatedFormType.updated_at).toLocaleString('vi-VN'),
                                    action: '<button class="btn btn-info detail_formtype" data-toggle="modal" data-target="#editFormTypeModal" data-id="' + updatedFormType.id + '">' +
                                        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">' +
                                            '<path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>' +
                                        '</svg>' +
                                    '</button>'
                                }).draw(false);
                            }
                        } else {
                            location.reload();
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({ icon: 'error', title: 'Lỗi', text: response.message });
                        }
                    }
                },
                error: function () {
                    alert('Đã xảy ra lỗi khi cập nhật loại biểu mẫu.');
                }
            });
        });

        $(document).on('click', '.detail_formtype', function (e) {
            e.preventDefault();
            var formTypeId = $(this).data('id');
            var editUrl = routes.formTypeEdit || '/admin/form-type/edit';

            $.ajax({
                type: 'GET',
                url: editUrl,
                data: { id: formTypeId },
                success: function (response) {
                    if (response.success) {
                        $('#view_id').val(response.data.id);
                        $('#edit_type_name').val(response.data.type_name);
                    } else {
                        if (typeof swal === 'function') {
                            swal({ icon: 'error', title: 'Lỗi', text: response.message });
                        }
                    }
                },
                error: function () {
                    alert('Không thể tải thông tin loại biểu mẫu.');
                }
            });
        });

        $(document).on('click', '.delete_form_type', function (e) {
            e.preventDefault();
            var form_id = $(this).data('form_id');
            var deleteUrl = routes.formTypeDelete || '/admin/form-type/delete';

            function executeDeleteType() {
                $.ajax({
                    type: 'POST',
                    url: deleteUrl,
                    data: { id: form_id },
                    success: function (response) {
                        if (response.success) {
                            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#FormTypeTable')) {
                                $('#FormTypeTable').DataTable().ajax.reload();
                            } else {
                                location.reload();
                            }
                            if (typeof swal === 'function') {
                                swal({ icon: 'success', title: 'Thành công', text: response.message });
                            }
                        } else {
                            if (typeof swal === 'function') {
                                swal({ icon: 'error', title: 'Lỗi', text: response.message });
                            }
                        }
                    },
                    error: function () {
                        alert('Đã xảy ra lỗi khi xóa loại biểu mẫu.');
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Bạn có chắc chắn muốn xóa?',
                    text: "Bạn sẽ không thể khôi phục lại dữ liệu này!",
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function (willDelete) {
                    if (willDelete) executeDeleteType();
                });
            } else {
                if (window.confirm("Bạn có chắc chắn muốn xóa loại biểu mẫu này?")) executeDeleteType();
            }
        });

        $('#createFormType').on('click', function (e) {
            e.preventDefault();
            var type_name = $('#type_name').val();
            var storeUrl = routes.formTypeStore || '/admin/form-type/store';

            $.ajax({
                type: 'POST',
                url: storeUrl,
                data: { type_name: type_name },
                success: function (response) {
                    if (response.success) {
                        if (typeof swal === 'function') {
                            swal({ icon: 'success', title: 'Thành công', text: response.message });
                        }
                        $('#createFormTypeModal').modal('hide');
                        $('#type_name').val('');
                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#FormTypeTable')) {
                            $('#FormTypeTable').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({ icon: 'error', title: 'Lỗi', text: response.message });
                        }
                    }
                },
                error: function () {
                    alert('Đã xảy ra lỗi khi tạo loại biểu mẫu.');
                }
            });
        });

        // ==========================================
        // 2. SUB FORM TYPE (Phân loại phụ)
        // ==========================================

        $(document).on('click', '.delete_sup_form_type', function (e) {
            e.preventDefault();
            var form_id = $(this).data('form_id');
            var deleteSupUrl = routes.supFormTypeDelete || '/admin/sup-form-type/delete';

            function executeDeleteSup() {
                $.ajax({
                    type: 'POST',
                    url: deleteSupUrl,
                    data: { id: form_id },
                    success: function (response) {
                        if (response.success) {
                            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#SupFormTypeTable')) {
                                $('#SupFormTypeTable').DataTable().ajax.reload();
                            } else {
                                location.reload();
                            }
                            if (typeof swal === 'function') {
                                swal({ icon: 'success', title: 'Thành công', text: response.message });
                            }
                        } else {
                            if (typeof swal === 'function') {
                                swal({ icon: 'error', title: 'Lỗi', text: response.message });
                            }
                        }
                    },
                    error: function () {
                        alert('Đã xảy ra lỗi khi xóa phân loại phụ.');
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Bạn có chắc chắn muốn xóa?',
                    text: "Bạn sẽ không thể khôi phục lại dữ liệu này!",
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function (willDelete) {
                    if (willDelete) executeDeleteSup();
                });
            } else {
                if (window.confirm("Bạn có chắc chắn muốn xóa phân loại phụ này?")) executeDeleteSup();
            }
        });

        $(document).on('click', '.detail_supformtype', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var editSupUrl = routes.supFormTypeEdit || '/admin/sup-form-type/edit';

            $.ajax({
                type: 'GET',
                url: editSupUrl,
                data: { id: id },
                success: function (response) {
                    if (response.success) {
                        $('#edit_form_type_id').val(response.data.form_type_id);
                        $('#edit_name').val(response.data.name);
                        $('#edit_description').val(response.data.description);
                        $('#edit_sup_form_type_id').val(response.data.id);
                    } else {
                        if (typeof swal === 'function') {
                            swal({ icon: 'error', title: 'Lỗi', text: response.message });
                        }
                    }
                },
                error: function () {
                    alert('Không thể tải thông tin phân loại phụ.');
                }
            });
        });

        $(document).on('click', '#updateSupFormType', function (e) {
            e.preventDefault();
            var form_type_id = $('#edit_form_type_id').val();
            var name = $('#edit_name').val();
            var description = $('#edit_description').val();
            var id = $('#edit_sup_form_type_id').val();
            var updateSupUrl = routes.supFormTypeUpdate || '/admin/sup-form-type/update';

            $.ajax({
                type: 'POST',
                url: updateSupUrl,
                data: {
                    id: id,
                    form_type_id: form_type_id,
                    name: name,
                    description: description
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#SupFormTypeTable')) {
                            $('#SupFormTypeTable').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                        $('#editSupFormTypeModal').modal('hide');
                        if (typeof swal === 'function') {
                            swal({ icon: 'success', title: 'Thành công', text: response.message });
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({ icon: 'error', title: 'Lỗi', text: response.message });
                        }
                    }
                },
                error: function () {
                    alert('Đã xảy ra lỗi khi cập nhật phân loại phụ.');
                }
            });
        });

        $('#createSupFormType').on('click', function (e) {
            e.preventDefault();
            var form_type_id = $('#form_type_id').val();
            var name = $('#name').val();
            var description = $('#description').val();
            var storeSupUrl = routes.supFormTypeStore || '/admin/sup-form-type/store';

            $.ajax({
                type: 'POST',
                url: storeSupUrl,
                data: {
                    form_type_id: form_type_id,
                    name: name,
                    description: description
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof swal === 'function') {
                            swal({ icon: 'success', title: 'Thành công', text: response.message });
                        }
                        $('#createSupFormTypeModal').modal('hide');
                        $('#form_type_id').val('');
                        $('#name').val('');
                        $('#description').val('');
                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#SupFormTypeTable')) {
                            $('#SupFormTypeTable').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({ icon: 'error', title: 'Lỗi', text: response.message });
                        }
                    }
                },
                error: function () {
                    alert('Đã xảy ra lỗi khi tạo phân loại phụ.');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormTypeManager);
    } else {
        initFormTypeManager();
    }

})(window, window.jQuery);
