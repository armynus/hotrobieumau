@extends('user.layouts.app')

@section('title', 'Tra cứu xã/phường sau sáp nhập')
<style>
   .container {
        color: #000;
        max-width: 1050px!important; /* Giới hạn chiều rộng */
    }
    .custom-border {
        border: 1px solid #9F9F9F!important;  /* màu xám đậm hơn */
        border-radius: 0.25rem!important; /* bo góc nhẹ */
        box-shadow: none!important;
    }
  /* Tùy chỉnh CSS cho thẻ thông tin */
  #pre .info-card {
    background-color: #fcfcfc; /* Màu nền nhạt giống ảnh mẫu */
    border: 1px solid #e9e9e9; /* Viền mỏng hơn */
    box-shadow: none; /* Bỏ đổ bóng mặc định của card */
    height: 100%; /* Đảm bảo các card trong cùng một hàng cao bằng nhau */
  }

  /* Tùy chỉnh tiêu đề phụ "Trước sáp nhập" */
  #pre .info-card .card-subtitle {
    font-size: 0.9rem;
    font-weight: 500;
  }

  /* Tùy chỉnh tiêu đề "Sau sáp nhập" */
  .section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #555;
    margin-top: 1.5rem; /* Tạo khoảng cách phía trên */
  }

  /* Tùy chỉnh đường kẻ ngang */
  .section-divider {
    margin-top: 0.5rem;
    margin-bottom: 1.5rem;
    border-top: 1px solid #ddd;
  }
</style>
@section('content')
<div class="container py-3">
  <h1 class="h3 mb-4">Tra cứu xã/phường trước & sau sáp nhập</h1>
  <!-- Nav Tabs -->
  <ul class="nav nav-tabs mb-3" id="lookupTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="pre-tab" data-toggle="tab" href="#pre" role="tab">Trước sáp nhập</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="post-tab" data-toggle="tab" href="#post" role="tab">Sau sáp nhập</a>
    </li>
  </ul>

  <div class="tab-content" id="lookupTabsContent">
    <!-- Trước sáp nhập -->
        
    <div class="tab-pane fade show active" id="pre" role="tabpanel">
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Tỉnh/thành trước sáp nhập</label>
                <input type="search" class="form-control bg-light border-1 small custom-border" placeholder="Chọn tỉnh..."
                    name="search_pre_province" id="search_pre_province" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label>Quận/huyện</label>
                <input type="search" class="form-control bg-light border-1 small custom-border" placeholder="Chọn huyện..."
                    name="search_pre_district" id="search_pre_district" autocomplete="off" disabled>
            </div>
            <div class="form-group col-md-4">
                <label>Phường/xã</label>
                <input type="search" class="form-control bg-light border-1 small custom-border" placeholder="Chọn xã..."
                    name="search_pre_ward" id="search_pre_ward" autocomplete="off" disabled>
            </div>
        </div>

        
        <hr class="section-divider" />
        <h4 class="section-title">Sau sáp nhập</h4>
        <div class="row" id="pre_results">
            <div class="col-lg-6 mb-4" id="pre_province_results">
                <div class="card info-card">
                    <div class="card-body custom-border">
                    <h6 class="card-subtitle">Tỉnh/Thành phố (mới):</h6>
                    <p class="card-text font-weight-bold mb-3" id="preResultProvince">&nbsp;</p>
                    <h6 class="card-subtitle text-muted">Trước sáp nhập:</h6>
                    <p class="card-text" id="preResultProvinceOld">&nbsp;</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4" id="pre_wards_results">
                <div class="card info-card">
                    <div class="card-body custom-border">
                    <h6 class="card-subtitle">Phường/Xã (mới):</h6>
                    <p class="card-text font-weight-bold mb-3" id="preResultWard">&nbsp;</p>
                    <h6 class="card-subtitle text-muted">Trước sáp nhập:</h6>
                    <p class="card-text" id="preResultWardOld">&nbsp;</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sau sáp nhập -->
    <div class="tab-pane fade" id="post" role="tabpanel">
      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Tỉnh/thành sau sáp nhập</label>
          <input type="search" class="form-control bg-light border-1 small custom-border" placeholder="Chọn tỉnh..."
                data-selected-id="1"    name="search_post_province" id="search_post_province" autocomplete="off" value="Đồng Tháp" disabled>
        </div>
        <div class="form-group col-md-6">
          <label>Phường/xã</label>
          <input type="search" class="form-control bg-light border-1 small custom-border" placeholder="Chọn xã..."
                name="search_post_ward" id="search_post_ward" autocomplete="off">
        </div>
      </div>
      <div class="card">
        <div class="card-body custom-border">
          <div class="row">
            <div class="col-md-8">
              <table class="table table-borderless mb-0">
                {{-- <tr>
                  <th class="p-2">Tỉnh/thành (mới):</th>
                  <td class="p-2 font-weight-bold" id="postResultProvince">Đồng Tháp</td>
                </tr>
                <tr>
                  <th class="p-2">Đơn vị cũ được sáp nhập:</th>
                  <td class="p-2" id="postResultProvinceOld"><strong>Đồng Tháp</strong>, Tiền Giang</td>
                </tr> --}}
                <tr>
                  <th class="p-2">Xã/phường (mới):</th>
                  <td class="p-2 font-weight-bold" id="postResultWard"></td>
                </tr>
                <tr>
                  <th class="p-2">Đơn vị cũ được sáp nhập:</th>
                  <td class="p-2" id="postResultWardOld"></td>
                </tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
{{-- js --}}
@push('scripts')
     <!-- Bootstrap core JavaScript-->
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <!-- Page level plugins -->
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <!-- Page level custom scripts -->
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    <!-- include jQuery validate library -->
    <script src="{{asset('js/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js')}}" type="text/javascript"></script>
    <!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
    <link href="{{ asset('vendor/jquery/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>

    @include('user.partials.merger_lookup_js')
    


    
      
    
@endpush