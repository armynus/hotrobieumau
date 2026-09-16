@extends('user.layouts.app')

@section('title', 'Quét mã QR')
<head>

  <style>
      #reader {
          width: 100%;
          max-width: 500px;
          margin: auto;
      }
  
      #camera-select {
          padding: 10px;
          font-size: 16px;
          border-radius: 8px;
          border: 1px solid #ccc;
          width: 300px;
          text-align: center;
      }

      #result {
          background: #f1f8e9;
          border-left: 6px solid #43a047;
          color: #33691e;
          font-size: 16px;
          padding: 10px 15px;
          border-radius: 6px;
          min-height: 40px;
          width: 100%;
          max-width: 600px;
          margin: auto;
      }

      .hidden {
        display: none;
      }
  </style>

</head>
@section('content')
<div class="container-fluid text-center">
  <h1 class="h3 mb-4 text-gray-800">Quét mã QR</h1>

  <div class="card shadow mb-4 d-flex justify-content-center align-items-center p-4">      
      <select id="camera-select" class="mb-3"></select>

      <div id="video-wrapper" style="position: relative; width: 100%; max-width: 600px; aspect-ratio: 4/3;">
        <video id="video" autoplay muted playsinline style="width: 100%; height: 100%; border-radius: 8px; border: 2px solid #ccc;"></video>
      </div>

      <div class="mt-3">
          <button id="start-scan" class="btn btn-success mx-2">▶️ Bắt đầu quét</button>
          <button id="stop-scan" class="btn btn-danger mx-2">⛔ Tắt quét</button>
      </div>

      <p id="result" class="mt-3">📡 Đang chờ quét...</p>
  </div>
</div>

@endsection
{{-- js --}}
@push('scripts')
     <!-- Bootstrap core JavaScript-->
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

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
    
     <!-- jsQR library - Thư viện quét QR code mạnh mẽ -->
    <script src="{{asset('js/jsQR.min.js')}}"></script>
    
    <script src="{{asset('js/user/scan_camera.js')}}"></script>

    
      
    
@endpush