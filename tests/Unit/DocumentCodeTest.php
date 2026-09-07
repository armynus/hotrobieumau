<?php

namespace Tests\Unit;

use App\Support\DocumentCode;
use PHPUnit\Framework\TestCase;

class DocumentCodeTest extends TestCase
{
    public function test_it_ignores_file_extension_accents_and_common_separators(): void
    {
        $this->assertSame(
            DocumentCode::normalize('01/NHNo.ĐT-QLRR'),
            DocumentCode::normalize('01-NHNo-DT_QLRR.pdf')
        );
    }
}
