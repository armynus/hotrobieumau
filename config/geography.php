<?php

return [
    // Shared reference data belongs to the main database, never a branch database.
    'connection' => env('GEOGRAPHY_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),
    'mapping_source_hash' => '4a3d93b9c9c21cb355ef1f7ee7e3be102d5311bdb60461264b107f88defd7466',
    'prepared' => [
        'dong-thap' => ['label' => 'Đồng Tháp đã đối chiếu (102 đơn vị)', 'path' => base_path('outputs/ward-review-2026-09-24/dong-thap-reviewed.json')],
        'national' => ['label' => 'Toàn quốc (còn dữ liệu cần kiểm chứng)', 'path' => base_path('outputs/ward-review-2026-09-24/normalized-review.json')],
    ],
    // These byte-identical sources retain the existing verification labels.
    // Other imports are usable immediately, but never claim official verification.
    'reviewed_hashes' => [
        'ec49681737051183a3f94f888fcfd95cae3cac787ae38078353078853582fdc6',
        '9f3a1470414743d4fd49724eac81d70754e5cc8c84c2be4f178cf7e34e5925b1',
    ],
];
