<script>
$(document).on("click", ".delete_form", function() {
    let formId = $(this).data("form_id");

    // Hiển thị cảnh báo xác nhận trước khi xóa
    swal({
        title: "Bạn có chắc chắn?",
        text: "Hành động này sẽ xóa biểu mẫu vĩnh viễn!",
        icon: "warning",
        buttons: ["Hủy", "Xóa"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            $.ajax({
                url: `/support_forms/${formId}/delete`, // API xử lý xóa
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"), // CSRF Token
                },
                success: function(response) {
                    if (response.status) {
                        swal("Thành công!", "Biểu mẫu đã được xóa.", "success");
                        // Xóa hàng của biểu mẫu trên giao diện
                        $('tr').has(`button[data-form_id="${formId}"]`).remove();
                    } else {
                        swal("Lỗi!", response.message || "Không thể xóa biểu mẫu.", "error");
                    }
                },
                error: function(xhr) {
                    swal("Lỗi!", "Đã xảy ra lỗi không xác định.", "error");
                },
            });
        }
    });
});
$(document).ready(function(){
    $('#editFormSubmit').click(function(e) {
        e.preventDefault(); // Ngăn chặn gửi form mặc định

        let formId = $("#editForm input[name='form_id']").val();
        let formName = $("#edit_form_name").val().trim();
        let fileInput = $("#edit_form_file")[0].files.length;
        let selectedFields = [];

        $("input[name='selected_fields[]']:checked").each(function() {
            selectedFields.push($(this).val());
        });

        if (formName === "") {
            swal("Vui lòng nhập tên biểu mẫu.");
            return;
        }
        if (selectedFields.length === 0) {
            swal("Vui lòng chọn ít nhất một trường dữ liệu.");
            return;
        }
        
        let formData = new FormData();
        formData.append("form_name", formName);
        formData.append("form_id", formId);
        selectedFields.forEach(field => {
            formData.append("selected_fields[]", field);
        });

        // Đảm bảo chỉ thêm file nếu có
        if (fileInput > 0) {
            formData.append("form_file", $("#edit_form_file")[0].files[0]);
        }
        // console.log([...formData]);
        
        $.ajax({
            url: '{{ route("support_forms_update") }}', // Đường dẫn route xử lý lưu biểu mẫu
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            },
            success: function(response) {
                if (response.status === true) {
                  
                    swal("Thành công!", response.message, "success").then(() => {
                       
                    });
                    const form = response.data;
                    let updated_at = new Date(form.updated_at);
                    let formatted_updated_at = updated_at.toLocaleDateString('en-GB');
                    const row = $(`button[data-form_id="${form.id}"]`).closest('tr');

                    row.find('td:nth-child(2)').text(form.name);
                    row.find('td:nth-child(3)').text(form.usage_count);
                    row.find('td:nth-child(4)').text(form.file_template);
                    row.find('td:nth-child(6)').text(formatted_updated_at);

                    $('#close_button').click();

                } else {
                    swal("Lỗi!", response.message || "Không thể cập nhật biểu mẫu.", "error");
                }
            },
            error: function(xhr) {
                let response = JSON.parse(xhr.responseText);
                if (xhr.status === 400) {
                    // Hiển thị thông báo lỗi chi tiết từ server
                    swal("Lỗi!", response.message || "File đã tồn tại hoặc có lỗi khi cập nhật.", "error");
                } else {
                    swal("Lỗi!", "Đã xảy ra lỗi khi cập nhật biểu mẫu.", "error");
                }
            }
        });
    });
    
});


$(document).on("click", ".edit_form", function() {
    let formId = $(this).data("form_id");
    $.ajax({
        url: `/support_forms/${formId}/edit`, // Route lấy dữ liệu biểu mẫu
        method: "GET",
        success: function(response) {
            if (response.status) {
                let form = response.data;
                // Điền dữ liệu vào các trường input
                $("#editForm input[name='form_name']").val(form.name);
                $("#editForm input[name='form_id']").val(form.id);

                // Đánh dấu các checkbox đã chọn
                let selectedFields = JSON.parse(form.fields);
                $("#editForm input[name='selected_fields[]']").each(function() {
                    $(this).prop("checked", selectedFields.includes($(this).val()));
                });

                // Hiển thị modal
            }
        },
        error: function(xhr) {
            swal("Lỗi!", "Không thể tải dữ liệu biểu mẫu.", "error");
        }
    });
});

$(document).ready(function(){
    $('#addFormSubmit').click(function(e) {
        e.preventDefault(); // Ngăn chặn gửi form mặc định

        let formName = $("#add_form_name").val().trim();
        let fileInput = $("#add_form_file")[0].files.length;
        let selectedFields = [];

        // Duyệt qua tất cả các checkbox được chọn và lấy giá trị
        $("input[name='selected_fields[]']:checked").each(function() {
            selectedFields.push($(this).val());
        });

        // Kiểm tra xem tên biểu mẫu có được nhập không
        if (formName === "") {
            swal("Vui lòng nhập tên biểu mẫu.");
            return;
        }

        if (selectedFields.length === 0) {
            swal("Vui lòng chọn ít nhất một trường dữ liệu.");
            return;
        }
        // Kiểm tra xem file có được tải lên không
        if (fileInput === 0) {
            swal("Vui lòng chọn file biểu mẫu.");
            return;
        }
        // Tạo dữ liệu gửi lên
        let formData = new FormData();
        formData.append("form_name", formName);
        formData.append("form_file", $("#add_form_file")[0].files[0]);
        $.each(selectedFields, function(index, value) {
            formData.append("selected_fields[]", value);
        });
        // console.log([...formData]);
        $.ajax({
            url: '{{ route("support_forms_create") }}', // Đường dẫn route xử lý lưu biểu mẫu
            method: 'POST',
            data: formData,
            processData: false,  // Vì sử dụng FormData nên không xử lý dữ liệu thành chuỗi
            contentType: false,  // Để trình duyệt tự set content type phù hợp với FormData
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Thêm CSRF Token
            },
            success: function(response) {
                if(response.status === true) {
                    var Form = response.data;
                    let created_at = new Date(Form.created_at); // Chuyển chuỗi thành đối tượng Date
                    let formatted_created_at = created_at.toLocaleDateString('en-GB'); // Định dạng dd/mm/yyyy

                    let updated_at = new Date(Form.updated_at);
                    let formatted_updated_at = updated_at.toLocaleDateString('en-GB');
                    // Thêm dòng mới
                    let newForm = `
                        <td>${Form.id}</td>
                        <td>${Form.name}</td>
                        <td>${Form.usage_count}</td>
                        <td>${Form.file_template}</td>
                        <td>${formatted_created_at}</td>
                        <td>${formatted_updated_at}</td>
                        <td style="justify-content: center; align-items: flex-start; text-align: center; " >
                            <button type="button"  data-toggle="modal" data-target="#editFormModal" class="btn btn-info btn-icon-split edit_form" data-form_id="${Form.id}">
                                <span class="text " >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                        <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                        <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                    </svg>
                                </span>
                            </button>
                            <button  class="btn btn-danger btn-icon-split">
                                <span class="icon text-white-50 cancel_order lock_form"  data-form_id="${Form.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                        </svg>
                                </span>
                            </button>
                        </td>
                    `;

                    $('table tbody').append(newForm);
                    swal({
                        title: "Thành công!",
                        text: response.message,
                        icon: "success"
                    }).then(function() {
                        // Ẩn modal sau khi thành công
                        $('#close_button').click();
                        // Reset form
                        $('#addForm')[0].reset();
                    });
                } else {
                    swal({
                        title: "Lỗi!",
                        text: response.message,
                        icon: "error"
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    console.log(xhr.responseJSON); // In ra lỗi chi tiết từ Laravel
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = "Có lỗi xảy ra:\n";
                    $.each(errors, function(key, messages) {
                        errorMessage += messages.join("\n") + "\n";
                    });

                    swal({
                        title: "Cảnh báo!",
                        text: errorMessage,
                        icon: "warning"
                    });
                } else {
                    swal({
                        title: "Lỗi!",
                        text: "Đã xảy ra lỗi không xác định.",
                        icon: "error"
                    });
                }
            }
        });
    });
});
</script>