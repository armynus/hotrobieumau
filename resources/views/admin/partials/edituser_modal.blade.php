<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="editUserModalLabel">Cập Nhật Tài Khoản Nhân Viên</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <input type="hidden" id="edit_user_id" name="edit_user_id">
                <div class="form-group">
                    <label for="edit_name">Tên Nhân Viên</label>
                    <input type="text" class="form-control" id="edit_name" name="edit_name" placeholder="Nhập tên nhân viên" >
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="text" class="form-control" id="edit_email" name="edit_email" placeholder="Nhập Email" >
                </div>
                <div class="form-group">
                    <label for="edit_password">Mật Khẩu</label>
                    <input type="password" class="form-control" id="edit_password" name="edit_password" placeholder="Nhập mật khẩu" >
                </div>
                {{-- Choose branch of user register --}}
                <div class="form-group">
                    <label for="branch">Chi Nhánh</label>
                    <select class="form-control" id="edit_branch" name="edit_branch">
                        <option value="">Chọn chi nhánh</option>
                        @foreach($list_branch as $branch)
                        <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_role_id">Chức Vụ</label>
                    <select class="form-control" id="edit_role_id" name="edit_role_id">
                        <option value="">Chọn chức vụ</option>
                        <option value="1">Kiểm soát</option>
                        <option value="2">Nhân viên</option>
                    </select>
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="button" form="editUserForm" class="btn btn-primary" id="updateUser">Lưu</button>
            </div>
        </div>
    </div>
</div>                     