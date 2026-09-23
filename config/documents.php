<?php

return [
    'ledger' => [
        // Mẫu đi cùng source để server nội bộ không cần Word hay internet khi tạo DOCX.
        'presentation_slip_template' => env('DOCUMENT_LEDGER_SLIP_TEMPLATE', resource_path('documents/ledger-presentation-slip.docx')),
        'web_import_max_rows' => (int) env('DOCUMENT_LEDGER_WEB_IMPORT_MAX_ROWS', 5000),
        'web_import_timeout' => (int) env('DOCUMENT_LEDGER_WEB_IMPORT_TIMEOUT', 180),
    ],
    'exports' => [
        // Sổ nhỏ tải trực tiếp; chỉ sổ lớn mới đưa qua worker nền.
        'max_rows' => (int) env('DOCUMENT_EXPORT_MAX_ROWS', 20000),
        'background_min_rows' => (int) env('DOCUMENT_EXPORT_BACKGROUND_MIN_ROWS', 5000),
        'chunk_size' => (int) env('DOCUMENT_EXPORT_CHUNK_SIZE', 500),
        'lock_seconds' => (int) env('DOCUMENT_EXPORT_LOCK_SECONDS', 300),
        'disk' => env('DOCUMENT_EXPORT_DISK', 'local'),
        'queue' => env('DOCUMENT_EXPORT_QUEUE', 'document-exports'),
        'job_timeout' => (int) env('DOCUMENT_EXPORT_JOB_TIMEOUT_SECONDS', 240),
        'ttl_hours' => (int) env('DOCUMENT_EXPORT_TTL_HOURS', 24),
    ],

    'metadata_extraction' => [
        // Để trống để tự tìm trong PATH, hoặc truyền đường dẫn tuyệt đối trên máy chủ.
        'pdftotext_binary' => env('DOCUMENT_PDFTOTEXT_BINARY'),
        'pdftoppm_binary' => env('DOCUMENT_PDFTOPPM_BINARY'),
        'tesseract_binary' => env('DOCUMENT_TESSERACT_BINARY'),
        'tesseract_languages' => env('DOCUMENT_TESSERACT_LANGUAGES', 'vie+eng'),
        'max_pages' => (int) env('DOCUMENT_OCR_MAX_PAGES', 2),
        'dpi' => (int) env('DOCUMENT_OCR_DPI', 220),
        'timeout_seconds' => (int) env('DOCUMENT_OCR_TIMEOUT_SECONDS', 150),
        'job_timeout_seconds' => (int) env('DOCUMENT_OCR_JOB_TIMEOUT_SECONDS', 180),
        'minimum_text_characters' => (int) env('DOCUMENT_OCR_MIN_TEXT_CHARACTERS', 80),
        'queue' => env('DOCUMENT_OCR_QUEUE', 'document-ocr'),
    ],
];
