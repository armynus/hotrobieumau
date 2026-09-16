<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\TemplateProcessor;

/** Cho phép dọn file tạm của PHPWord khi xuất mẫu thất bại trước saveAs. */
class DocumentLedgerTemplateProcessor extends TemplateProcessor
{
    public function __construct($template)
    {
        try {
            parent::__construct($template);
        } catch (\Throwable $exception) {
            // Khi constructor lỗi, PHP không gọi destructor của đối tượng chưa tạo xong.
            parent::__destruct();
            if (isset($this->tempDocumentFilename)) {
                File::delete($this->tempDocumentFilename);
            }
            throw $exception;
        }
    }

    public function temporaryPath(): string
    {
        return $this->tempDocumentFilename;
    }
}
