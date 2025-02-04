<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route($actionRoute) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $modalLabel }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <div class="form-group">
                        <label for="custom_file">File dữ liệu tài khoản</label>
                        <div class="col-sm-9">
                            <div class="input-file-container">  
                                <input class="input-file data_customer" id="{{ $inputName }}" type="file" name="{{ $inputName }}" accept=".xls,.xlsx,.xlsm,.xlsb,.csv">
                                <label tabindex="0" for="{{ $inputName }}" class="input-file-trigger">Chọn file</label>
                            </div>
                            <p class="file-return"></p>
                        </div>                              
                    </div>
                </div>
                

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
