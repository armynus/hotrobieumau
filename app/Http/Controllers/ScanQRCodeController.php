<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScanQRCodeController extends Controller
{
    public function index()
    {
        return view('user.page.scan_qr_code');
    }

    public function scan(Request $request)
    {
        // Handle the scanned QR code data
        $qrCodeData = $request->input('qr_code_data');

        // Process the QR code data as needed
        // For example, you can save it to the database or perform some action

        return response()->json(['message' => 'QR code scanned successfully!', 'data' => $qrCodeData]);
    }
}
