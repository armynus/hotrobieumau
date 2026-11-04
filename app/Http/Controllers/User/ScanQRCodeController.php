<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SupportForm;
use Illuminate\Http\Request;

class ScanQRCodeController extends Controller
{
    public function index()
    {
        $forms = SupportForm::with(['formType', 'supFormType'])
            ->orderBy('name')
            ->get(['id', 'name', 'form_type', 'sup_form_type_id', 'fields']);

        return view('user.page.scan_qr_code', compact('forms'));
    }

    public function scan(Request $request)
    {
        $qrCodeData = (string) $request->input('qr_code_data', '');

        return response()->json([
            'message' => 'QR code scanned successfully!',
            'data' => $qrCodeData,
        ]);
    }
}
