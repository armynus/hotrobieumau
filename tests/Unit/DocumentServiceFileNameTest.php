<?php

namespace Tests\Unit;

use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class DocumentServiceFileNameTest extends TestCase
{
    public function test_it_keeps_the_original_name_and_adds_a_counter_on_collision(): void
    {
        Storage::fake('public');
        $folder = 'documents/2026/08/27';
        $service = app(DocumentService::class);
        $method = new ReflectionMethod($service, 'uniqueStoredFileName');
        $method->setAccessible(true);

        $this->assertSame(
            'Quyết định.pdf',
            $method->invoke($service, $folder, 'Quyết định.pdf')
        );

        Storage::disk('public')->put($folder . '/Quyết định.pdf', 'first');
        Storage::disk('public')->put($folder . '/Quyết định (1).pdf', 'second');

        $this->assertSame(
            'Quyết định (2).pdf',
            $method->invoke($service, $folder, 'Quyết định.pdf')
        );
    }
}
