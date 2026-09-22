<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DataImport;
use Illuminate\Http\JsonResponse;

class DataImportController extends Controller
{
    public function show(DataImport $dataImport): JsonResponse
    {
        abort_unless(
            (int) $dataImport->user_id === (int) session('user_id')
                && (int) $dataImport->branch_id === (int) session('UserBranchId'),
            404
        );

        $total = (int) ($dataImport->total_rows ?? 0);
        $processed = (int) $dataImport->processed_rows;

        return response()->json([
            'id' => $dataImport->id,
            'status' => $dataImport->status,
            'file_name' => $dataImport->original_name,
            'total_rows' => $total,
            'processed_rows' => $processed,
            'inserted_rows' => (int) $dataImport->inserted_rows,
            'updated_rows' => (int) $dataImport->updated_rows,
            'skipped_rows' => (int) $dataImport->skipped_rows,
            'progress' => $total > 0 ? min(100, (int) round($processed * 100 / $total)) : 0,
            'error_message' => $dataImport->status === 'failed' ? $dataImport->error_message : null,
        ]);
    }
}
