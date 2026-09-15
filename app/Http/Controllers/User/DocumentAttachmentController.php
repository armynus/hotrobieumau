<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocumentAttachment;
use App\Models\User;
use App\Services\DocumentQueryService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class DocumentAttachmentController extends Controller
{
    public function __invoke(int $attachment, DocumentQueryService $query)
    {
        $user = User::find(Session::get('user_id'));
        abort_unless($user, 401);
        $file = DocumentAttachment::findOrFail($attachment);
        abort_unless($query->getDocumentsForUser($user)->whereKey($file->document_id)->exists(), 404);
        $root = realpath(Storage::disk('public')->path('documents'));
        $path = realpath(Storage::disk('public')->path($file->file_path));
        abort_unless($root && $path && is_file($path)
            && str_starts_with(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $root), '/').'/'), 404);
        // Không cho HTML/SVG hoặc file thực thi chạy trên cùng origin ứng dụng.
        $inline = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf'
            && (new \finfo(FILEINFO_MIME_TYPE))->file($path) === 'application/pdf';

        return response()->download($path, $file->file_name ?: basename($path), [
            'Content-Type' => $inline ? 'application/pdf' : 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => 'sandbox',
        ], $inline ? 'inline' : 'attachment');
    }
}
