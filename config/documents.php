<?php

return [
    'ledger' => [
        'web_import_max_rows' => (int) env('DOCUMENT_LEDGER_WEB_IMPORT_MAX_ROWS', 5000),
        'web_import_timeout' => (int) env('DOCUMENT_LEDGER_WEB_IMPORT_TIMEOUT', 180),
    ],
    'exports' => [
        // Giới hạn xuất đồng bộ để một yêu cầu không chiếm hết RAM/CPU của máy chủ.
        'max_rows' => (int) env('DOCUMENT_EXPORT_MAX_ROWS', 20000),
        'chunk_size' => (int) env('DOCUMENT_EXPORT_CHUNK_SIZE', 500),
        'lock_seconds' => (int) env('DOCUMENT_EXPORT_LOCK_SECONDS', 300),
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
        'minimum_text_characters' => (int) env('DOCUMENT_OCR_MIN_TEXT_CHARACTERS', 80),
        'queue' => env('DOCUMENT_OCR_QUEUE', 'document-ocr'),
    ],
];
