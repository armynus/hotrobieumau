
<script>
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#addCustomer').click(function () {
            const fields = [
                'add_custno', 'add_name', 'add_nameloc', 'add_gender', 'add_birthday', 'add_phone_no',
                'add_identity_no', 'add_identity_date', 'add_identity_place', 'add_addrtpcd',
                'add_addr1', 'add_addr2', 'add_addr3', 'add_addrfull', 'add_custtpcd', 'add_custdtltpcd',
                'add_branch_code',
            ];

            let formData = {
                _token: $('input[name="_token"]').val(), // CSRF token
            };

            fields.forEach(field => {
                formData[field] = $('#' + field).val();
            });

            $.ajax({
                url: '{{ route('add_customer') }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === true) {
                        var Customer = response.customer;
                            // Xử lý các giá trị null thành khoảng trắng
                            Customer.id = Customer.id || '';
                            Customer.custno = Customer.custno || '';
                            Customer.nameloc = Customer.nameloc || '';
                            Customer.phone_no = Customer.phone_no || '';
                            Customer.identity_no = Customer.identity_no || '';
                            Customer.addrfull = Customer.addrfull || '';
                        if(response.avaiable === true){
                            // xác định row
                            const row = $(`button[data-id="${Customer.id}"]`).closest('tr');
                            row.find('td:nth-child(3)').text(Customer.nameloc);
                            row.find('td:nth-child(4)').text(Customer.phone_no);
                            row.find('td:nth-child(5)').text(Customer.identity_no);
                            row.find('td:nth-child(6)').text(Customer.addrfull);
                        }else{
                            
                            let newCustomer=`
                                <td>${Customer.id}</td>
                                <td>${Customer.custno}</td>
                                <td>${Customer.nameloc}</td>
                                <td>${Customer.phone_no}</td>
                                <td>${Customer.identity_no}</td>
                                <td>${Customer.addrfull}</td>
                                <td>
                                    <button class="btn btn-info btn-icon-split detail_customer" data-toggle="modal" data-target="#customerInfoModal" data-id="${Customer.id}">
                                        <span class="text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </td>
                            `;
                            
                            // Chèn dòng dữ liệu mới vào bảng
                            $('table tbody').append(newCustomer);
                        }
                        swal({
                            title: "Thành công!",
                            text: response.message,
                            icon: "success",
                        }).then(() => {
                            $('#close_button').click();
                            fields.forEach(field => {
                                $('#' + field).val('');
                            });
                        });
                    } else {
                        swal({
                            title: "Lỗi!",
                            text: response.message || "Thêm thông tin khách hàng thất bại",
                            icon: "error",
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.messages || ['Dữ liệu không hợp lệ.'];
                        swal({
                            title: "Cảnh báo!",
                            text: errors.join("\n"),
                            icon: "warning",
                        });
                    } else {
                        swal({
                            title: "Lỗi!",
                            text: "Đã xảy ra lỗi trong quá trình xử lý. Vui lòng thử lại.",
                            icon: "error",
                        });
                    }
                },
            });
        });

        $('#updateCustomer').click(function () {
            const customer_id = $('#view_id').val();
            const fields = [
                'name', 'nameloc', 'gender', 'birthday', 'phone_no',
                'identity_no', 'identity_date', 'identity_place', 'addrtpcd',
                'addr1', 'addr2', 'addr3', 'addrfull', 'custtpcd', 'custdtltpcd',
                'branch_code',
            ];

            let formData = {
                _token: $('input[name="_token"]').val(),
                id: customer_id
            };

            fields.forEach(field => {
                formData[field] = $('#' + field).val();
            });

            $.ajax({
                url: '{{ route('update_customer') }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === true) {
                        swal("Cập nhật thông tin khách hàng thành công", { icon: "success" });

                        const updatedCustomer = response.customer;
                        const row = $(`button[data-id="${updatedCustomer.id}"]`).closest('tr');

                        row.find('td:nth-child(3)').text(updatedCustomer.nameloc);
                        row.find('td:nth-child(4)').text(updatedCustomer.phone_no);
                        row.find('td:nth-child(5)').text(updatedCustomer.identity_no);
                        row.find('td:nth-child(6)').text(updatedCustomer.addrfull);

                        $('#close_button').click();
                    } else {
                        alert('Cập nhật thông tin khách hàng thất bại');
                    }
                },
                error: function (xhr) {
                    console.error('Lỗi:', xhr.responseText);
                }
            });
        });

        $('.detail_customer').click(function () {
            const customer_id = $(this).data('id');
            console.log(customer_id);
            const fields = [
                'custno', 'name', 'nameloc', 'gender', 'birthday', 'phone_no',
                'identity_no', 'identity_date', 'identity_place', 'addrtpcd',
                'addr1', 'addr2', 'addr3', 'addrfull', 'custtpcd', 'custdtltpcd',
                'branch_code', 'created_at', 'updated_at'
            ];

            fields.forEach(field => $('#' + field).val(''));
            $('#view_id').val(customer_id);

            $.ajax({
                url: '{{ route('detail_customer') }}',
                method: 'GET',
                data: { id: customer_id },
                success: function (data) {
                    const dateFields = ['birthday', 'identity_date', 'created_at', 'updated_at'];

                    fields.forEach(field => {
                        if (dateFields.includes(field)) {
                            if (data.user[field]) {
                                const isoDate = new Date(data.user[field]);
                                const formattedDate = isoDate.toISOString().split('T')[0];
                                $('#' + field).val(formattedDate);
                            }
                        } else {
                            $('#' + field).val(data.user[field] || '');
                        }
                    });
                },
                error: function (data) {
                    console.log(data);
                }
            });
        });
    });
</script>

<script>
    // Giới hạn ngày sinh và ngày cấp CMND/CCCD
    document.addEventListener('DOMContentLoaded', function () {
        const birthday = document.getElementById('birthday'); // Lấy thẻ input
        const dateInput = document.getElementById('identity_date'); // Lấy thẻ input

        // Lấy ngày hiện tại
        const today = new Date();
        const maxDate = today.toISOString().split('T')[0]; // Chuyển sang định dạng yyyy-mm-dd

        // Gán ngày tối đa cho thẻ input
        birthday.setAttribute('max', maxDate);
        dateInput.setAttribute('max', maxDate);
    });
    // Xử lý khi nhập địa chỉ 1, 2, 3
    document.addEventListener('DOMContentLoaded', function () {
        const addr1 = document.getElementById('addr1');
        const addr2 = document.getElementById('addr2');
        const addr3 = document.getElementById('addr3');
        const addrfull = document.getElementById('addrfull');
        const add_addr1 = document.getElementById('add_addr1');
        const add_addr2 = document.getElementById('add_addr2');
        const add_addr3 = document.getElementById('add_addr3');
        const add_addrfull = document.getElementById('add_addrfull');

        // Gán sự kiện cho các thẻ input    
        addr1.addEventListener('input', function () {
            addrfull.value = `${addr1.value} ${addr2.value} ${addr3.value}`;
        });
        addr2.addEventListener('input', function () {
            addrfull.value = `${addr1.value} ${addr2.value} ${addr3.value}`;
        });
        addr3.addEventListener('input', function () {
            addrfull.value = `${addr1.value} ${addr2.value} ${addr3.value}`;
        });

        // Gán sự kiện cho các thẻ input
        add_addr1.addEventListener('input', function () {
            add_addrfull.value = `${add_addr1.value} ${add_addr2.value} ${add_addr3.value}`;
        });
        add_addr2.addEventListener('input', function () {
            add_addrfull.value = `${add_addr1.value} ${add_addr2.value} ${add_addr3.value}`;
        });
        add_addr3.addEventListener('input', function () {
            add_addrfull.value = `${add_addr1.value} ${add_addr2.value} ${add_addr3.value}`;
        });
    });

</script>