
<script>
    $(document).ready(function(){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('#addAccount').click(function () {
            const fields = [
                'add_idxacno', 'add_custseq', 'add_custnm', 'add_stscd', 'add_ccycd',
                'add_lmtmtp', 'add_minlmt', 'add_addr1', 'add_addr2', 'add_addr3', 'add_addrfull',
            ];

            let formData = {
                _token: $('input[name="_token"]').val(), // CSRF token
            };

            fields.forEach(field => {
                formData[field] = $('#' + field).val();
            });

            $.ajax({
                url: '{{ route('add_account') }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === true) {
                        var Account = response.account;

                         // Xử lý các giá trị null thành chuỗi rỗng
                        Account.id = Account.id || '';
                        Account.idxacno = Account.idxacno || '';
                        Account.custseq = Account.custseq || '';
                        Account.custnm = Account.custnm || '';
                        Account.stscd = Account.stscd || '';
                        Account.addrfull = Account.addrfull || '';
                        
                        if (response.avaiable === true) {
                            // Xác định dòng cần cập nhật
                            const row = $(`button[data-id="${Account.id}"]`).closest('tr');
                            row.find('td:nth-child(2)').text(Account.idxacno);
                            row.find('td:nth-child(3)').text(Account.custseq);
                            row.find('td:nth-child(4)').text(Account.custnm);
                            row.find('td:nth-child(5)').text(Account.stscd);
                            row.find('td:nth-child(6)').text(Account.addrfull);
                        } else {
                            // Thêm dòng mới
                            let newAccount = `
                                <td>${Account.id}</td>
                                <td>${Account.idxacno}</td>
                                <td>${Account.custseq}</td>
                                <td>${Account.custnm}</td>
                                <td>${Account.stscd}</td>
                                <td>${Account.addrfull}</td>
                                <td>
                                     <button class="btn btn-info btn-icon-split detail_account" 
                                            data-toggle="modal" 
                                            data-target="#AccountInfoModal" 
                                            data-id="${Account.id}">
                                        <span class="text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                        </span>
                                    </button>
                                </td>
                            `;

                            $('table tbody').append(newAccount);
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
                            text: response.message || "Thêm thông tin tài khoản thất bại",
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

        // Xử lý khi click vào nút cập nhật thông tin khách hàng
        $('#updateAccount').click(function () {
            const id = $('#view_id').val();
            const fields = [
                'custseq', 'custnm', 'stscd','ccycd','lmtmtp','minlmt',       
                'addr1',  'addr2', 'addr3','addrfull',     
            ];

            let formData = {
                _token: $('input[name="_token"]').val(),
                id: id
            };

            fields.forEach(field => {
                formData[field] = $('#' + field).val();
            });
           

            $.ajax({
                url: '{{ route('update_account') }}',
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === true) {
                        console.log(response)
                        console.log(response.account)
                        // Hiển thị thông báo thành công
                        swal("Cập nhật thông tin khách hàng thành công", { icon: "success" });
                        // Cập nhật trực tiếp thông tin trên bảng
                        const updatedAccount = response.account;
                        const row = $(`button[data-id="${updatedAccount.id}"]`).closest('tr');
                        // Thay đổi nội dung các cột
                        row.find('td:nth-child(3)').text(updatedAccount.custseq);  // Tên KH
                        row.find('td:nth-child(4)').text(updatedAccount.custnm); // Số điện thoại
                        row.find('td:nth-child(5)').text(updatedAccount.stscd); // CMND/CCCD
                        row.find('td:nth-child(6)').text(updatedAccount.addrfull);  // Địa chỉ

                
                    } else {
                        alert('Cập nhật thông tin khách hàng thất bại');
                    }
                },
                error: function (xhr) {
                    console.error('Lỗi:', xhr.responseText);
                }
            });
        });
        
        // Xử lý khi click vào nút xem thông tin khách hàng
        $('.detail_account').click(function(){
            const account_id = $(this).data('id');
            // Danh sách các trường cần reset
            const fields = [
                'idxacno','custseq', 'custnm' ,'stscd' ,'ccycd',
                'lmtmtp','minlmt','addr1','addr2','addr3' ,'addrfull','created_at','updated_at',
            ];

            // Xóa dữ liệu cũ
            fields.forEach(field => $('#' + field).val(''));
            // Gán giá trị cho trường ẩn
            $('#view_id').val(account_id);
            // Gửi request lấy dữ liệu mới
            $.ajax({
                url: '{{ route('detail_account') }}',
                method: 'GET',
                data: { id: account_id },
                success: function (data) {
                    // Danh sách các trường cần định dạng lại ngày
                    const dateFields = [ 'created_at', 'updated_at'];

                    fields.forEach(field => {
                        if (dateFields.includes(field)) {
                            // Chuyển định dạng ngày từ `2025-01-14T08:12:18.000000Z` sang `yyyy-MM-dd`
                            if (data.user[field]) {
                                const isoDate = new Date(data.user[field]);
                                const formattedDate = isoDate.toISOString().split('T')[0]; // Lấy phần yyyy-MM-dd
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