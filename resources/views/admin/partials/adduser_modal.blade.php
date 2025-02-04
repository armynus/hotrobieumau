<div class="modal fade" id="addUserhModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="addUserModalLabel">Thêm Tài Khoản Nhân Viên</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <div class="form-group">
                    <label for="name">Tên Nhân Viên</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Nhập tên nhân viên" >
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" class="form-control" id="email" name="email" placeholder="Nhập Email" >
                </div>
                <div class="form-group">
                    <label for="password">Mật Khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" >
                </div>
                {{-- Choose branch of user register --}}
                <div class="form-group">
                    <label for="branch">Chi Nhánh</label>
                    <select class="form-control" id="branch" name="branch">
                        <option value="">Chọn chi nhánh</option>
                        @foreach($list_branch as $branch)
                        <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="role_id">Chức Vụ</label>
                    <select class="form-control" id="role_id" name="role_id">
                        <option value="">Chọn chức vụ</option>
                        <option value="1">Kiểm soát</option>
                        <option value="2">Nhân viên</option>
                    </select>
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="submit" form="addUserForm" class="btn btn-primary" id="addUser">Thêm</button>
            </div>
        </div>
    </div>
</div>