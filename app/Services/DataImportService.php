<?php

namespace App\Services;

use App\Jobs\ProcessDataImport;
use App\Models\DataImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DataImportService
{
    public function queue(
        UploadedFile $file,
        string $type,
        int $userId,
        int $branchId,
        string $tenantDatabase
    ): DataImport {
        if (! in_array($type, ['customer', 'account'], true)) {
            throw new RuntimeException('Loại dữ liệu nhập không hợp lệ.');
        }

        $extension = mb_strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xls', 'xlsx', 'xlsm', 'csv'], true)) {
            throw new RuntimeException('Chỉ hỗ trợ file XLS, XLSX, XLSM hoặc CSV.');
        }

        $disk = 'local';
        $path = Storage::disk($disk)->putFileAs(
            "data-imports/{$branchId}",
            $file,
            Str::uuid().'.'.$extension
        );
        if (! $path) {
            throw new RuntimeException('Không lưu được file nhập lên máy chủ.');
        }

        $import = null;
        try {
            $import = DataImport::create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'type' => $type,
                'tenant_database' => $tenantDatabase,
                'original_name' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'status' => 'queued',
            ]);

            ProcessDataImport::dispatch($import->id);

            return $import;
        } catch (Throwable $exception) {
            $import?->delete();
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }
}
