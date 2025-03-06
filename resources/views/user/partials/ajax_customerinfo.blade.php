
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
                    if (!response.status) {
                        swal({
                            title: "Lỗi!",
                            text: response.message || "Thêm thông tin khách hàng thất bại",
                            icon: "error",
                        });
                        return;
                    }

                    var Customer = response.customer;

                    // Xử lý giá trị null thành chuỗi rỗng để tránh lỗi
                    Customer.id = Customer.id || '';
                    Customer.custno = Customer.custno || '';
                    Customer.nameloc = Customer.nameloc || '';
                    Customer.phone_no = Customer.phone_no || '';
                    Customer.identity_no = Customer.identity_no || '';

                    let table = $('#dataTable').DataTable(); // Lấy instance của DataTables

                    if (response.avaiable === true) {
                        // 🔹 CẬP NHẬT khách hàng đã có trong bảng
                        let row = $(`button[data-id="${Customer.id}"]`).closest('tr');
                        let dataRow = table.row(row);

                        if (dataRow.data()) { // Kiểm tra nếu hàng tồn tại trong DataTable
                            let rowData = dataRow.data(); // Lấy dữ liệu hiện có của hàng

                            // Chỉ cập nhật cột dữ liệu, giữ nguyên cột button
                            rowData[0] = Customer.id;
                            rowData[1] = Customer.custno;
                            rowData[2] = Customer.nameloc;
                            rowData[3] = Customer.phone_no;
                            rowData[4] = Customer.identity_no;

                            dataRow.data(rowData).draw(false); // Cập nhật mà không reset pagination
                        }
                    } else {
                        // 🔹 THÊM MỚI khách hàng vào bảng
                        table.row.add([
                            Customer.id,
                            Customer.custno,
                            Customer.nameloc,
                            Customer.phone_no,
                            Customer.identity_no,
                            `<td style="text-align: center;">
                                <button class="btn btn-info btn-icon-split detail_customer" 
                                        data-toggle="modal" 
                                        data-target="#customerInfoModal" 
                                        data-id="${Customer.id}">
                                    <span class="text">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                            <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                        </svg>
                                    </span>
                                </button>
                            </td>`
                        ]).draw(false); // Thêm mà không reset pagination
                    }

                    // Hiển thị thông báo thành công
                    swal({
                        title: "Thành công!",
                        text: response.message,
                        icon: "success",
                    }).then(() => {
                        $('#close_button').click();
                        if (typeof fields !== 'undefined') { // Kiểm tra nếu fields tồn tại
                            fields.forEach(field => {
                                $('#' + field).val('');
                            });
                        }
                    });
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
    });
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
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
                        let table = $('#dataTable').DataTable(); // Lấy instance của DataTable

                        // Tìm hàng dựa vào ID khách hàng
                        let rowIndex = table.rows().eq(0).filter(function (index) {
                            return table.cell(index, 0).data() == updatedCustomer.id;
                        });

                        if (rowIndex.length) {
                            // Cập nhật hàng trong DataTable
                            table.row(rowIndex).data([
                                updatedCustomer.id,
                                updatedCustomer.custno,
                                updatedCustomer.nameloc,
                                updatedCustomer.phone_no,
                                updatedCustomer.identity_no,
                                `<td style="text-align: center;">
                                    <button class="btn btn-info btn-icon-split detail_customer" 
                                            data-toggle="modal" 
                                            data-target="#customerInfoModal" 
                                            data-id="${updatedCustomer.id}">
                                        <span class="text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                        </span>
                                    </button>
                                </td>`
                            ]).draw(false); // Cập nhật mà không reset pagination
                        }

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

    });
    $(document).on('click', '.detail_customer', function () {
        var customer_id = $(this).data('id');
        $.ajax({
            url: '{{ route('detail_customer') }}',
            method: 'GET',
            data: { id: customer_id },
            success: function (data) {
                var fields = [
                    'custno', 'name', 'nameloc', 'gender', 'birthday', 'phone_no',
                    'identity_no', 'identity_date', 'identity_place', 'addrtpcd',
                    'addr1', 'addr2', 'addr3', 'addrfull', 'custtpcd', 'custdtltpcd',
                    'branch_code', 'created_at', 'updated_at'
                ];

                fields.forEach(field => $('#' + field).val(''));
                $('#view_id').val(customer_id);
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