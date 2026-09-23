<?php

namespace App\Services;

use App\Models\ItSupportRequest;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ItSupportStorage
{
    public function pendingDirectory(ItSupportRequest $ticket): string
    {
        return "it-support/CHO-XU-LY/PHIEU-{$ticket->id}";
    }

    public function archiveDirectory(ItSupportRequest $ticket, CarbonInterface $date): string
    {
        return sprintf('it-support/NAM %s/THANG %s-%s/NGAY %s-%s-%s/PHIEU-%d',
            $date->format('Y'), $date->format('m'), $date->format('Y'),
            $date->format('d'), $date->format('m'), $date->format('Y'), $ticket->id);
    }

    /** @param UploadedFile[] $files */
    public function storeUploads(ItSupportRequest $ticket, array $files): void
    {
        $stored = [];
        try {
            foreach ($files as $file) {
                $name = $this->safeName($file->getClientOriginalName());
                $path = $file->storeAs($this->pendingDirectory($ticket), Str::uuid().'.'.$file->extension(), 'local');
                if (! $path) {
                    throw new RuntimeException('Không lưu được tệp đính kèm.');
                }
                $stored[] = ['path' => $path, 'name' => $name];
            }
            $ticket->attachment_paths = $stored;
            $ticket->save();
        } catch (\Throwable $error) {
            foreach ($stored as $item) {
                Storage::disk('local')->delete($item['path']);
            }
            throw $error;
        }
    }

    /**
     * Copy first, update the DB record, then remove old copies. Safe to retry after interruption.
     * @return array<int, array{disk: string, path: string}>
     */
    public function secureAndArchive(ItSupportRequest $ticket): array
    {
        $directory = $ticket->completed_at
            ? $this->archiveDirectory($ticket, $ticket->completed_at)
            : $this->pendingDirectory($ticket);
        $obsolete = [];
        $secured = [];

        foreach ($ticket->attachmentFiles() as $file) {
            $oldPath = $file['path'];
            $sourceDisk = str_starts_with($oldPath, 'it_supports/') ? 'public' : 'local';
            if ($sourceDisk === 'local' && ! str_starts_with($oldPath, 'it-support/')) {
                throw new RuntimeException('Đường dẫn tệp IT không hợp lệ.');
            }
            if (! Storage::disk($sourceDisk)->exists($oldPath)) {
                throw new RuntimeException("Không tìm thấy tệp của phiếu #{$ticket->id}: {$oldPath}");
            }
            if ($sourceDisk === 'local' && str_starts_with($oldPath, $directory.'/')) {
                $secured[] = $file;
                continue;
            }

            $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));
            $newPath = $directory.'/'.Str::uuid().($extension ? '.'.$extension : '');
            $stream = Storage::disk($sourceDisk)->readStream($oldPath);
            if ($stream === false) {
                throw new RuntimeException('Không đọc được tệp đính kèm.');
            }
            try {
                if (! Storage::disk('local')->put($newPath, $stream)) {
                    throw new RuntimeException('Không sao chép được tệp vào kho riêng.');
                }
            } finally {
                fclose($stream);
            }
            $secured[] = ['path' => $newPath, 'name' => $this->safeName($file['name'])];
            $obsolete[] = ['disk' => $sourceDisk, 'path' => $oldPath];
        }

        $ticket->attachment_paths = $secured;
        $ticket->save();
        if ($ticket->completed_at) {
            $this->writeManifest($ticket);
        }
        return $obsolete;
    }

    public function writeManifest(ItSupportRequest $ticket): void
    {
        if (! $ticket->completed_at) {
            return;
        }
        $ticket->loadMissing('user.branch', 'events');
        $document = [
            'ticket_id' => $ticket->id,
            'submitted_at' => $ticket->created_at?->toIso8601String(),
            'completed_at' => $ticket->completed_at->toIso8601String(),
            'status' => $ticket->status,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'category' => $ticket->category,
            'contact_phone' => $ticket->contact_phone,
            'requester' => ['id' => $ticket->user_id, 'name' => $ticket->user?->name, 'branch' => $ticket->user?->branch?->branch_name],
            'resolution_note' => $ticket->resolution_note,
            'attachments' => $ticket->attachmentFiles(),
            'history' => $ticket->events->map(fn ($event) => [
                'at' => $event->created_at?->toIso8601String(),
                'actor' => $event->actor_name,
                'actor_type' => $event->actor_type,
                'from' => $event->from_status,
                'to' => $event->to_status,
                'message' => $event->message,
            ])->all(),
        ];
        $path = $this->archiveDirectory($ticket, $ticket->completed_at).'/PHIEU-'.$ticket->id.'.json';
        if (! Storage::disk('local')->put($path, json_encode($document, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Không lưu được hồ sơ lưu trữ IT.');
        }
    }

    public function download(ItSupportRequest $ticket, int $index)
    {
        $file = $ticket->attachmentFiles()[$index] ?? null;
        abort_unless($file, 404);
        $path = $file['path'];
        $disk = str_starts_with($path, 'it_supports/') ? 'public' : 'local';
        abort_unless(str_starts_with($path, $disk === 'public' ? 'it_supports/' : 'it-support/'), 404);
        $root = realpath(Storage::disk($disk)->path($disk === 'public' ? 'it_supports' : 'it-support'));
        $absolute = realpath(Storage::disk($disk)->path($path));
        abort_unless($root && $absolute && is_file($absolute) && str_starts_with(
            str_replace('\\', '/', $absolute), rtrim(str_replace('\\', '/', $root), '/').'/'
        ), 404);
        return response()->download($absolute, $this->safeName($file['name']), [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => 'sandbox',
        ]);
    }

    private function safeName(string $name): string
    {
        return mb_substr(preg_replace('/[\\x00-\\x1F\\x7F\\/\\\\]+/u', '_', basename(str_replace('\\', '/', $name))) ?: 'tep-dinh-kem', 0, 180);
    }
}
