<?php
// Local synthetic fixture. Run: php -S 127.0.0.1:8793 -t public tests/browser/form-workspace-smoke.php
// Only the explicit PHP development-server router uses this fixture; production routes are unchanged.
if (is_file(__DIR__.'/../../public'.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) return false;
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$request = Illuminate\Http\Request::capture();
$app->instance('request', $request);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$directory = storage_path('framework/testing');
if (!is_dir($directory)) mkdir($directory, 0777, true);
$sessions = $directory.'/form-workspace-sessions';
if (!is_dir($sessions)) mkdir($sessions, 0777, true);
$database = $directory.'/form-workspace-smoke.sqlite';
if (!is_file($database)) touch($database);
config(['database.default' => 'mysql', 'database.connections.mysql' => ['driver' => 'sqlite', 'database' => $database, 'prefix' => ''], 'database.connections.tenant' => ['driver' => 'sqlite', 'database' => $database, 'prefix' => ''], 'session.driver' => 'file', 'session.files' => $sessions, 'session.cookie' => 'form_workspace_smoke', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('mysql');
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8793');
$schema = Illuminate\Support\Facades\Schema::getFacadeRoot();
if (!$schema->hasTable('support_forms')) {
    $schema->create('support_forms', function ($table) { $table->id(); $table->string('name'); $table->text('fields'); $table->string('file_template'); $table->integer('form_type')->default(1); $table->integer('sup_form_type_id')->nullable(); $table->integer('usage_count')->default(0); $table->timestamps(); });
    $schema->create('form_fields', function ($table) { $table->id(); $table->string('field_code'); $table->string('field_name'); $table->string('data_type')->default('text'); $table->string('placeholder')->nullable(); $table->string('value')->nullable(); $table->string('content_group')->default('transaction'); $table->unsignedInteger('display_order')->default(0); });
    $schema->create('form_type', function ($table) { $table->id(); $table->string('type_name'); });
    $schema->create('sup_form_type', function ($table) { $table->id(); $table->string('name'); });
    $schema->create('users', fn ($table) => $table->id());
    $schema->create('documents', function ($table) { $table->id(); $table->timestamps(); });
    (require database_path('migrations/main/2025_09_15_095723_support_form_usages.php'))->up();
    (require database_path('migrations/main/2025_09_16_074656_support_form_draft.php'))->up();
    Illuminate\Support\Facades\DB::table('users')->insert(['id' => 1]);
    Illuminate\Support\Facades\DB::table('form_type')->insert([['id' => 1, 'type_name' => 'Khách hàng cá nhân'], ['id' => 2, 'type_name' => 'Khách hàng tổ chức']]);
    foreach (['nameloc' => ['Họ và tên', 'text'], 'birthday' => ['Ngày sinh', 'date'], 'identity_no' => ['Số CCCD', 'text'], 'idxacno' => ['Số tài khoản', 'text'], 'SoThe' => ['Bốn số cuối thẻ', 'text'], 'MobileBanking' => ['Dịch vụ ngân hàng', 'text'], 'NgayGiaoDich' => ['Ngày giao dịch', 'date']] as $code => [$name, $type]) {
        Illuminate\Support\Facades\DB::table('form_fields')->insert(['field_code' => $code, 'field_name' => $name, 'data_type' => $type]);
    }
    foreach (['Đề nghị mở tài khoản thanh toán', 'Đăng ký phát hành thẻ ghi nợ', 'Đăng ký dịch vụ ngân hàng điện tử', 'Thay đổi thông tin khách hàng', 'Ủy quyền giao dịch', 'Đề nghị xác nhận số dư'] as $index => $name) {
        $codes = ['nameloc', 'birthday', 'identity_no', 'idxacno', 'NgayGiaoDich', $index % 2 ? 'SoThe' : 'MobileBanking'];
        $template = '_form_workspace_smoke_'.($index + 1).'.docx';
        $word = new PhpOffice\PhpWord\PhpWord(); $section = $word->addSection();
        $section->addText('DỮ LIỆU KIỂM THỬ — '.$name);
        foreach ($codes as $code) $section->addText($code.': ${'.$code.'}');
        PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save(public_path('storage/'.$template));
        App\Models\SupportForm::create(['name' => $name, 'fields' => $codes, 'file_template' => $template, 'form_type' => $index < 4 ? 1 : 2]);
    }
}
if (!$schema->hasTable('customer_info')) {
    foreach ([new App\Models\CustomerInfo(), new App\Models\AccountInfo()] as $model) {
        $schema->create($model->getTable(), function ($table) use ($model) { $table->id(); $table->timestamps(); foreach ($model->getFillable() as $column) $table->string($column)->nullable(); });
    }
    App\Models\CustomerInfo::create(['custno' => '000123', 'nameloc' => 'Nguyễn Văn Kiểm Thử', 'custtpcd' => 'Cá nhân']);
}
$app->instance(App\Http\Middleware\TenantDatabase::class, new class {
    public function handle($request, $next) {
        $request->session()->put(['user_id' => 1, 'user_name' => 'Kiểm thử giao diện', 'UserBranchName' => 'Dữ liệu giả']);
        return $next($request);
    }
});
// Replace the lookup only in the local fixture. All form, draft and bundle routes stay real.
Illuminate\Support\Facades\Route::get('/customers/search', fn () => response()->json([['label' => 'Khách hàng kiểm thử — CIF 000123', 'value' => 'Khách hàng kiểm thử', 'customer' => ['custno' => '000123', 'nameloc' => 'Nguyễn Văn Kiểm Thử', 'birthday' => '1990-02-15', 'identity_no' => '000000000001', 'custtpcd' => 'Cá nhân', 'accounts' => [['idxacno' => '0001234567890', 'ccycd' => 'VND']]]]]));
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
