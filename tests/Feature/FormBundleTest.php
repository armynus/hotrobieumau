<?php

namespace Tests\Feature;

use App\Models\SupportForm;
use App\Services\FormWorkspaceService;
use App\Services\SupportFormBundleDocumentService;
use App\Services\SupportFormDocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class FormBundleTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql', 'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('mysql');
        Schema::create('support_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('fields');
            $table->string('file_template');
            $table->integer('form_type')->default(1);
            $table->integer('sup_form_type_id')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('field_code');
            $table->string('field_name');
            $table->string('data_type')->default('text');
            $table->string('placeholder')->nullable();
            $table->string('value')->nullable();
            $table->string('content_group')->default('transaction');
            $table->unsignedInteger('display_order')->default(0);
        });
        Schema::create('form_type', fn (Blueprint $table) => [$table->id(), $table->string('type_name')]);
        Schema::create('sup_form_type', fn (Blueprint $table) => [$table->id(), $table->string('name')]);
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('documents', fn (Blueprint $table) => [$table->id(), $table->timestamps()]);
        (require database_path('migrations/main/2025_09_15_095723_support_form_usages.php'))->up();
        (require database_path('migrations/main/2025_09_16_074656_support_form_draft.php'))->up();
        DB::table('users')->insert(['id' => 1]);
        DB::table('form_type')->insert(['id' => 1, 'type_name' => 'Giao dịch']);
        DB::table('form_fields')->insert([
            ['field_code' => 'nameloc', 'field_name' => 'Tên khách hàng', 'content_group' => 'customer', 'display_order' => 10],
            ['field_code' => 'SoThe', 'field_name' => 'Số thẻ', 'content_group' => 'account', 'display_order' => 10],
            ['field_code' => 'MobileBanking', 'field_name' => 'Dịch vụ', 'content_group' => 'account', 'display_order' => 20],
        ]);
        cache()->put('form_types', collect(), 60);
        $this->withSession(['user_id' => 1]);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function makeForm(string $name, array $codes, bool $legacy = false): SupportForm
    {
        $filename = '_test_bundle_'.bin2hex(random_bytes(8)).'.docx';
        $path = public_path('storage/'.$filename);
        $this->temporaryFiles[] = $path;
        $word = new PhpWord;
        $section = $word->addSection();
        foreach ($codes as $code) {
            $section->addText($code.': ${'.$code.'}');
        }
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return SupportForm::create(['name' => $name, 'fields' => $legacy ? json_encode($codes) : $codes, 'file_template' => $filename, 'form_type' => 1]);
    }

    private function bundle(): array
    {
        return [$this->makeForm('Mở tài khoản', ['nameloc', 'MobileBanking']), $this->makeForm('Đăng ký thẻ', ['nameloc', 'SoThe'], true)];
    }

    private function requestBody(array $forms): array
    {
        return ['form_ids' => array_map(fn ($form) => $form->id, $forms),
            'signature' => app(FormWorkspaceService::class)->signature(collect($forms)),
            'payload' => ['nameloc' => 'Nguyễn Văn A & B', 'SoThe' => '0123', 'MobileBanking' => ['MB_SMS']]];
    }

    public function test_workspace_merges_shared_fields_and_uses_the_shared_draft(): void
    {
        $forms = $this->bundle();
        $response = $this->get('/form-bundle?'.http_build_query(['form_ids' => array_column($forms, 'id')]))->assertOk();
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'id="nameloc"'));
        $this->assertStringContainsString('Chung 2 mẫu', $html);
        $this->assertStringNotContainsString('Dùng trong:', $html);
        $this->assertStringContainsString('formKey: "supportForm"', $html);
        $this->assertStringContainsString('Tải bộ Word', $html);
        $this->assertStringNotContainsString('Tải bộ Word (.zip)', $html);
    }

    public function test_complete_word_contains_each_form_with_blank_page_separator_and_preserves_data(): void
    {
        $forms = $this->bundle();
        $response = $this->postJson('/form-bundle/download', $this->requestBody($forms))->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('.docx', $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $bundleXml = $zip->getFromName('word/document.xml');
        $this->assertStringNotContainsString('<w:altChunk', $bundleXml);
        $this->assertSame(2, substr_count($bundleXml, 'w:val="nextPage"'));
        $this->assertSame(2, substr_count($bundleXml, 'Nguyễn Văn A &amp; B'));
        $this->assertStringContainsString('0123', $bundleXml);
        $this->assertStringNotContainsString('${nameloc}', $bundleXml);
        $this->assertNotFalse($zip->locateName('word/headerBundleBlank.xml'));
        $this->assertNotFalse($zip->locateName('word/footerBundleBlank.xml'));
        $zip->close();
        $this->assertSame([1, 1], SupportForm::orderBy('id')->pluck('usage_count')->all());
        // There are no customer/account tables in this test: an accidental write fails.
    }

    public function test_four_target_templates_merge_with_sections_media_headers_and_footnotes_intact(): void
    {
        $paths = array_map(fn ($name) => public_path('storage/forms/supportform/dich-vu-the/'.$name), [
            'Mau1G DVTK-CN QD 428.docx',
            'Mau 01 DN PH THE Debit kiêm hợp đồng.docx',
            'Mau 01aNHDT VB 641 AD 2.4.2026.docx',
            'Mau 02_NHDT VB 641 AD 2.4.2026.docx',
        ]);
        foreach ($paths as $path) {
            $this->assertFileExists($path);
        }

        $merged = app(SupportFormBundleDocumentService::class)->merge($paths);
        $this->temporaryFiles[] = $merged;
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($merged));
        $document = new \DOMDocument;
        $this->assertTrue($document->loadXML($zip->getFromName('word/document.xml')));
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        $text = implode('', array_map(fn ($node) => $node->nodeValue, iterator_to_array($xpath->query('//w:t'))));
        $positions = array_map(fn ($needle) => mb_strpos($text, $needle), [
            'ĐĂNG KÝ THÔNG TIN KHÁCH HÀNG', 'Mẫu 01/THE', 'Mẫu 01a/NHĐT', 'Mẫu 02/NHĐT',
        ]);
        foreach ($positions as $position) {
            $this->assertNotFalse($position);
        }
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertSame(7, $xpath->query('//w:sectPr')->length);
        $this->assertGreaterThanOrEqual(3, $xpath->query('//w:footnoteReference')->length);
        foreach (['//w:bookmarkStart/@w:id', '//wp:docPr/@id', '//w:sdtPr/w:id/@w:val'] as $query) {
            $ids = array_map(fn ($node) => $node->nodeValue, iterator_to_array($xpath->query($query)));
            $this->assertSame(count($ids), count(array_unique($ids)));
        }

        $relationships = new \DOMDocument;
        $this->assertTrue($relationships->loadXML($zip->getFromName('word/_rels/document.xml.rels')));
        $relationshipIds = [];
        foreach ($relationships->getElementsByTagName('Relationship') as $relationship) {
            $relationshipIds[$relationship->getAttribute('Id')] = true;
        }
        foreach ($xpath->query('//@r:id | //@r:embed | //@r:link') as $reference) {
            $this->assertArrayHasKey($reference->nodeValue, $relationshipIds);
        }
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $this->assertGreaterThanOrEqual(4, count(array_filter($entries, fn ($name) => str_starts_with($name, 'word/media/'))));
        $this->assertContains('word/headerBundleBlank.xml', $entries);
        $this->assertContains('word/footerBundleBlank.xml', $entries);
        $zip->close();
    }

    public function test_invalid_selection_is_rejected_before_export(): void
    {
        $forms = $this->bundle();
        foreach ([[], [$forms[0]->id], [$forms[0]->id, $forms[0]->id], range(1, 11), [$forms[0]->id, 999]] as $ids) {
            $this->postJson('/form-bundle/download', ['form_ids' => $ids])->assertStatus(422);
        }
    }

    public function test_stale_template_and_nested_payload_are_rejected(): void
    {
        $forms = $this->bundle();
        $body = $this->requestBody($forms);
        $forms[0]->update(['name' => 'Mẫu đã thay đổi']);
        $this->postJson('/form-bundle/download', $body)->assertStatus(422)->assertJsonValidationErrors('signature');
        $body = $this->requestBody($forms);
        $body['payload']['nameloc'] = [['unexpected' => 'nested']];
        $this->postJson('/form-bundle/download', $body)->assertStatus(422);
        $this->assertSame(0, SupportForm::sum('usage_count'));
    }

    public function test_missing_template_never_returns_partial_bundle(): void
    {
        $forms = $this->bundle();
        $body = $this->requestBody($forms);
        unlink(public_path('storage/'.$forms[1]->file_template));
        $this->postJson('/form-bundle/download', $body)->assertStatus(422);
        $this->assertSame(0, SupportForm::sum('usage_count'));
    }

    public function test_failure_mid_bundle_does_not_increment_usage_and_removes_generated_files(): void
    {
        $forms = $this->bundle();
        $body = $this->requestBody($forms);
        $generated = tempnam(sys_get_temp_dir(), 'bundle_failed_');
        $this->temporaryFiles[] = $generated;
        file_put_contents($generated, 'first result');
        $this->mock(SupportFormDocumentService::class, function ($mock) use ($generated) {
            $mock->shouldReceive('generate')->once()->ordered()->andReturn($generated);
            $mock->shouldReceive('generate')->once()->ordered()->andThrow(new \RuntimeException('Synthetic failure'));
        });
        $this->postJson('/form-bundle/download', $body)->assertStatus(500);
        $this->assertFileDoesNotExist($generated);
        $this->assertSame(0, SupportForm::sum('usage_count'));
    }

    public function test_catalog_and_legacy_single_form_still_render(): void
    {
        $forms = $this->bundle();
        $this->get('/forms')->assertOk()->assertSee('Tạo bộ hồ sơ')->assertSee('Mở tài khoản');
        $this->get('/support_forms/1/'.$forms[1]->id)->assertOk()->assertSee('Thông tin khách hàng')->assertSee('Tải Word');
    }

    public function test_download_requires_authenticated_session(): void
    {
        $this->flushSession();
        $this->postJson('/form-bundle/download', [])->assertStatus(401);
    }

    public function test_single_download_creates_word_without_customer_database_writes(): void
    {
        $form = $this->makeForm('Mẫu đơn', ['nameloc', 'custno']);
        $response = $this->postJson('/transaction_form_print', ['form_id' => $form->id, 'nameloc' => 'Tên chỉ dùng để in', 'custno' => '000123'])->assertOk();
        $this->temporaryFiles[] = $response->baseResponse->getFile()->getPathname();
        $this->assertSame(1, $form->fresh()->usage_count);
    }

    private function tenantTables(): void
    {
        config(['database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('tenant');
        foreach ([new \App\Models\CustomerInfo, new \App\Models\AccountInfo] as $model) {
            Schema::connection('tenant')->create($model->getTable(), function (Blueprint $table) use ($model) {
                $table->id();
                $table->timestamps();
                foreach ($model->getFillable() as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
    }

    public function test_explicit_save_preserves_blank_fields_and_links_new_account_to_customer(): void
    {
        $this->tenantTables();
        $form = $this->makeForm('Khách hàng', ['custno', 'nameloc', 'phone_no', 'idxacno', 'ccycd']);
        \App\Models\CustomerInfo::create(['custno' => '000123', 'nameloc' => 'Tên cũ', 'phone_no' => '0900000000']);
        $this->postJson('/support-form/customer', ['form_id' => $form->id, 'payload' => [
            'custno' => '000123', 'nameloc' => 'Tên mới', 'phone_no' => '', 'idxacno' => '000999', 'ccycd' => 'VND', 'identity_no' => 'UNDECLARED',
        ]])->assertOk();
        $customer = \App\Models\CustomerInfo::first();
        $this->assertSame('Tên mới', $customer->nameloc);
        $this->assertSame('0900000000', $customer->phone_no);
        $this->assertNull($customer->identity_no);
        $this->assertSame('000123', \App\Models\AccountInfo::first()->custseq);
    }

    public function test_customer_save_rolls_back_if_account_belongs_to_another_customer(): void
    {
        $this->tenantTables();
        $form = $this->makeForm('Khách hàng', ['custno', 'nameloc', 'idxacno']);
        \App\Models\CustomerInfo::create(['custno' => 'A', 'nameloc' => 'Tên gốc']);
        \App\Models\AccountInfo::create(['idxacno' => '000999', 'custseq' => 'B']);
        $this->postJson('/support-form/customer', ['form_id' => $form->id, 'payload' => ['custno' => 'A', 'nameloc' => 'Không được ghi', 'idxacno' => '000999']])->assertStatus(422);
        $this->assertSame('Tên gốc', \App\Models\CustomerInfo::first()->nameloc);
        $this->assertSame('B', \App\Models\AccountInfo::first()->custseq);
        $this->postJson('/support-form/customer', ['form_id' => $form->id, 'payload' => ['nameloc' => 'Không có CIF']])->assertStatus(422);
    }
}
