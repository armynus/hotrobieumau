
<script>
    document.getElementById('pasteClipboardAddCusTomerBtn').addEventListener('click', function () {
        try {
            const fieldMapping = {
                'custno': 'custno',
                'nmloc': 'add_nameloc',
                'nm'    : 'add_name',
                'regno': 'add_identity_no',
                'issuedt1' : 'add_identity_date',
                'identity_place' : 'add_identity_place',
                'custtpcd'  : 'add_custtpcd',
                'custdtltpcd' : 'add_custdtltpcd',
                'ctrycdnatl': 'add_ctrycdnatl',
                'idxacno': 'add_idxacno',
                'ccycd'  : 'add_ccycd',
                'name_1' : 'add_birthday',
                'name_2' : 'add_branch_code',
                'name_3' : 'add_gender',
                'name_4' : 'add_phone_no',
                'name_5' : 'add_profnm',
                'addrtpcd' : 'add_addrtpcd',
                'addr1': 'add_addr1',
                'addr2': 'add_addr2',
                'addr3': 'add_addr3',
                'taxno' : 'add_taxno',
                'taxno_date' : 'issuedt6',
                'busno' : 'add_busno',
                'busno_date' : 'issuedt4',
                'usridop1': 'add_usridop1',
                // Thêm ánh xạ khác nếu cần
            };

            const readFromClipboard = function(callback) {
                if (navigator.clipboard && navigator.clipboard.readText) {
                    navigator.clipboard.readText()
                        .then(text => callback(text))
                        .catch(err => {
                            console.error("Lỗi đọc clipboard API:", err);
                            fallbackReadFromClipboard(callback);
                        });
                    return;
                }
                fallbackReadFromClipboard(callback);
            };

            const fallbackReadFromClipboard = function(callback) {
                const textarea = document.createElement('textarea');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                
                const handlePaste = function(e) {
                    const clipboardData = e.clipboardData || window.clipboardData;
                    const pastedText = clipboardData.getData('text');
                    callback(pastedText);
                    document.removeEventListener('paste', handlePaste);
                    document.body.removeChild(textarea);
                };
                
                document.addEventListener('paste', handlePaste);
                textarea.focus();
                alert("Hãy nhấn Ctrl+V để dán dữ liệu từ clipboard");
                
                setTimeout(() => {
                    if (document.body.contains(textarea)) {
                        document.removeEventListener('paste', handlePaste);
                        document.body.removeChild(textarea);
                    }
                }, 10000);
            };

            readFromClipboard(function(text) {
                if (!text) {
                    alert("Clipboard trống!");
                    return;
                }

                const rows = text.trim().split(/\n/);
                const headers = rows[0].split(/\t/).map(h => h.trim().toLowerCase());
                const values = rows[1].split(/\t/);
        
                headers.forEach((header, index) => {
                    const mappedField = fieldMapping[header] || header;
                    let input = document.querySelector(`.modal-content input[name='${mappedField}'], .modal-content select[name='${mappedField}']`);

                    if (values[index] !== undefined) {
                        let value = values[index].trim();

                        // Nếu input là kiểu "date" và giá trị có định dạng YYYYMMDD -> Chuyển sang YYYY-MM-DD
                        if (input && input.type === "date" && /^\d{8}$/.test(value)) {
                            value = `${value.substring(0, 4)}-${value.substring(4, 6)}-${value.substring(6, 8)}`;
                        }

                        // Xử lý địa chỉ: Ghép addr1, addr2, addr3 thành addrfull
                        if (['addr1', 'addr2', 'addr3'].includes(header)) {
                            if (!window.addrParts) window.addrParts = {}; // Lưu giá trị tạm thời
                            window.addrParts[header] = value;

                            const fullAddress = ['addr1', 'addr2', 'addr3']
                                .map(field => window.addrParts[field] || '') // Lấy giá trị addr1, addr2, addr3 (nếu có)
                                .filter(part => part !== '') // Xóa khoảng trống
                                .join(' ');

                            let addrInput = document.querySelector(`.modal-content input[name='add_addrfull']`);
                            if (addrInput) addrInput.value = fullAddress;
                        } else if (input) {
                            input.value = value;
                        }
                        // Gắn dữ liệu vào thẻ input hidden custno
                        if (['custno'].includes(header)) {
                            let custnoInput = document.querySelector(`.modal-content input[name='custno_hidden']`);
                            if (custnoInput) {
                                custnoInput.value = value;
                            }
                        }
                    }
                });

            });
        } catch (error) {
            console.error("Lỗi:", error);
            alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
        }
    });
    $(document).ready(function () {
        $('#customerTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('customers.data') }}", // Route lấy dữ liệu
            columns: [
                { data: 'id', name: 'id' },
                { data: 'custno', name: 'custno' },
                { data: 'nameloc', name: 'nameloc' },
                { data: 'phone_no', name: 'phone_no' },
                { data: 'identity_no', name: 'identity_no' },
                {
                    data: 'id',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `<button class="btn btn-info detail_customer" 
                                    data-toggle="modal" 
                                    data-target="#customerInfoModal" 
                                    data-id="${data}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                </button>`;
                    }
                }
            ]
        });
    });

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
                'add_branch_code', 'add_profnm', 'add_usridop1', 'add_identity_outdate',
                'add_taxno', 'add_taxno_date', 'add_taxno_place', 'add_busno', 'add_busno_date', 'add_busno_place',
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

                    let table = $('#customerTable').DataTable(); // Lấy instance của DataTables

                    if (response.avaiable === true) {
                        // 🔹 CẬP NHẬT khách hàng đã có trong bảng
                        let row = $(`button[data-id="${Customer.id}"]`).closest('tr');
                        let dataRow = table.row(row);

                        if (dataRow.data()) {
                            // Cập nhật từng key trong object
                            let updatedData = {
                                ...dataRow.data(), // giữ nguyên action button
                                id: Customer.id,
                                custno: Customer.custno,
                                nameloc: Customer.nameloc,
                                phone_no: Customer.phone_no,
                                identity_no: Customer.identity_no
                            };

                            dataRow.data(updatedData).draw(false);
                        }
                    } else {
                        // 🔹 THÊM MỚI khách hàng vào bảng
                        table.row.add({
                            id: Customer.id,
                            custno: Customer.custno,
                            nameloc: Customer.nameloc,
                            phone_no: Customer.phone_no,
                            identity_no: Customer.identity_no,
                            action: `
                                <td style="text-align: center;">
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
                        }).draw(false);
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
                'branch_code', 'profnm', 'usridop1', 'identity_outdate',
                'taxno', 'taxno_date', 'taxno_place', 'busno', 'busno_date', 'busno_place',
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
                        let table = $('#customerTable').DataTable(); // Lấy instance của DataTable

                        // Tìm hàng dựa vào ID khách hàng
                        let rowIndex = table.rows().eq(0).filter(function (index) {
                            return table.cell(index, 0).data() == updatedCustomer.id;
                        });

                        if (rowIndex.length) {
                            // Cập nhật hàng trong DataTable
                            table.row(rowIndex).data({
                                id: updatedCustomer.id,
                                custno: updatedCustomer.custno,
                                nameloc: updatedCustomer.nameloc,
                                phone_no: updatedCustomer.phone_no,
                                identity_no: updatedCustomer.identity_no,
                                
                            }).draw(false); // Cập nhật mà không reset pagination
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
                    'branch_code', 'created_at', 'updated_at', 'profnm', 'usridop1', 'identity_outdate',
                    'taxno', 'taxno_date', 'taxno_place', 'busno', 'busno_date', 'busno_place',
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
        const add_birthday = document.getElementById('add_birthday'); // Lấy thẻ input
        const add_dateInput = document.getElementById('add_identity_date'); // Lấy thẻ input

        // Lấy ngày hiện tại
        const today = new Date();
        const maxDate = today.toISOString().split('T')[0]; // Chuyển sang định dạng yyyy-mm-dd

        // Gán ngày tối đa cho thẻ input
        birthday.setAttribute('max', maxDate);
        dateInput.setAttribute('max', maxDate);
        add_birthday.setAttribute('max', maxDate);
        add_dateInput.setAttribute('max', maxDate);
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