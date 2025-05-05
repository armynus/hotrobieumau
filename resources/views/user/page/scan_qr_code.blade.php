@extends('user.layouts.app')

@section('title', 'Biểu mẫu giao dịch')
<head>
  {{-- <script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js"></script> --}}
  <script src="{{asset('js/zxing-libary.index.min.js')}}"></script>
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
      <x-alert-message />
      
      <select id="camera-select" class="mb-3"></select>

      <div id="video-wrapper" style="position: relative;">
          <video id="video" width="500" height="380" autoplay muted style="border-radius: 8px; border: 2px solid #ccc;"></video>
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
    {{-- include HTML5 QR Code JS --}}
    
    <script>
      document.addEventListener("DOMContentLoaded", () => {
    const videoElement = document.getElementById('video');
    const selectElement = document.getElementById('camera-select');
    const resultElement = document.getElementById('result');
    const startBtn = document.getElementById('start-scan');
    const stopBtn = document.getElementById('stop-scan');

    const codeReader = new ZXing.BrowserMultiFormatReader();
    let currentDeviceId = null;
    let scanning = false;
    let controls = null;

    // Bắt đầu quét QR từ camera
    async function startScan(deviceId) {
        try {
            if (scanning) {
                console.warn("Đã đang quét. Reset lại...");
                await codeReader.reset();
                if (controls) controls.stop();
            }

            scanning = true;
            resultElement.textContent = "📡 Đang quét...";
            currentDeviceId = deviceId;

            codeReader.decodeFromVideoDevice(deviceId, videoElement, (result, error, c) => {
                controls = c;
                if (result && scanning) {
                    scanning = false;
                    console.log("✅ Đã quét:", result.getText());
                    resultElement.textContent = `✅ Mã QR: ${result.getText()}`;
                    if (controls) controls.stop();
                    codeReader.reset();
                }
            });
        } catch (err) {
            console.error("❌ Lỗi khi bắt đầu quét:", err);
            resultElement.textContent = "❌ Không thể bắt đầu quét.";
        }
    }

    // Tắt camera và dừng quét
    async function stopScan() {
        try {
            if (scanning || controls) {
                console.log("⛔ Dừng quét.");
                scanning = false;
                if (controls) controls.stop();
                await codeReader.reset();
                resultElement.textContent = "⏹️ Đã tắt quét.";
            }
        } catch (err) {
            console.error("❌ Lỗi khi dừng quét:", err);
        }
    }

    // Lấy danh sách camera
    codeReader.listVideoInputDevices().then(devices => {
        if (!devices.length) {
            alert("❌ Không tìm thấy camera.");
            return;
        }

        // Populate select box
        devices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.text = device.label || `Camera ${index + 1}`;
            selectElement.appendChild(option);
        });

        currentDeviceId = devices[0].deviceId;

        // Event listeners
        startBtn.addEventListener('click', () => {
            const selectedId = selectElement.value;
            startScan(selectedId);
        });

        stopBtn.addEventListener('click', stopScan);

        selectElement.addEventListener('change', (e) => {
            if (scanning) {
                stopScan().then(() => {
                    startScan(e.target.value);
                });
            }
        });

    }).catch(err => {
        console.error("❌ Không truy cập được camera:", err);
        alert("⚠️ Trình duyệt đang chặn camera hoặc không hỗ trợ.");
    });
});


      </script>
      
    
@endpush