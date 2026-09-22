<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocumentExportOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentExportStatusController extends Controller
{
    public function show(DocumentExportOperation $documentExport): JsonResponse
    {
        $this->authorizeOperation($documentExport);
        $expired = $documentExport->status === 'completed'
            && $documentExport->expires_at
            && $documentExport->expires_at->isPast();

        return response()->json([
            'id' => $documentExport->id,
            'status' => $expired ? 'expired' : $documentExport->status,
            'file_name' => $documentExport->file_name,
            'row_count' => (int) $documentExport->row_count,
            'error_message' => $expired
                ? 'File đã hết hạn. Vui lòng tạo lại.'
                : ($documentExport->status === 'failed' ? $documentExport->error_message : null),
            'download_url' => $documentExport->status === 'completed' && ! $expired
                ? route('documents_export_download', $documentExport)
                : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function download(DocumentExportOperation $documentExport): StreamedResponse
    {
        $this->authorizeOperation($documentExport);
        abort_unless(
            $documentExport->status === 'completed'
                && (! $documentExport->expires_at || $documentExport->expires_at->isFuture())
                && Storage::disk($documentExport->disk)->exists($documentExport->path),
            404,
            'File xuất không còn trên máy chủ.'
        );

        return Storage::disk($documentExport->disk)->download(
            $documentExport->path,
            $documentExport->file_name,
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function authorizeOperation(DocumentExportOperation $documentExport): void
    {
        abort_unless((int) $documentExport->user_id === (int) session('user_id'), 404);
    }
}
