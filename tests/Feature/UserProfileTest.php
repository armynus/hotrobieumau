<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'profile_testing', 'database.connections.profile_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('profile_testing');
        Storage::fake('local');

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->string('branch_code')->nullable();
            $table->string('branch_type')->default('type_2');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('department_name');
            $table->string('department_code')->nullable();
            $table->timestamps();
        });
        Schema::create('positions', function (Blueprint $table): void {
            $table->id();
            $table->string('position_name');
            $table->unsignedInteger('level')->default(999);
            $table->timestamps();
        });
        Schema::create('transaction_offices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('office_name');
            $table->string('office_code')->nullable();
            $table->string('office_address')->nullable();
            $table->string('office_place')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('office_email')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('transaction_office_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('user_ipcas')->nullable();
            $table->string('employee_code')->nullable();
            $table->string('citizen_id')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('password');
            $table->string('role_id');
            $table->string('document_role')->default('user');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::table('branches')->insert([
            'id' => 1, 'branch_name' => 'Agribank Đồng Tháp', 'branch_code' => '6500',
            'branch_type' => 'type_1', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('departments')->insert([
            'id' => 2, 'branch_id' => 1, 'department_name' => 'Phòng Khách hàng', 'department_code' => 'PKH',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('positions')->insert([
            'id' => 3, 'position_name' => 'Giao dịch viên', 'level' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('transaction_offices')->insert([
            'id' => 6, 'branch_id' => 1, 'office_name' => 'Phòng giao dịch Sa Đéc',
            'office_code' => 'PGD-SD', 'office_address' => '12 Nguyễn Huệ', 'office_place' => 'Đồng Tháp',
            'office_phone' => '0277 123 4567', 'office_email' => 'sadec@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'id' => 4, 'branch_id' => 1, 'department_id' => 2, 'transaction_office_id' => 6, 'position_id' => 3,
            'name' => 'Nguyễn Văn A', 'email' => 'a@example.test', 'user_ipcas' => 'NVA001', 'phone_no' => '0912345678',
            'password' => bcrypt('secret1'), 'role_id' => '2', 'document_role' => 'user',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_user_can_view_own_profile_and_organization_information(): void
    {
        $this->withSession($this->sessionData())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Thông tin cá nhân')
            ->assertSee('a@example.test')
            ->assertSee('NVA001')
            ->assertSee('0912345678')
            ->assertSee('Agribank Đồng Tháp')
            ->assertSee('Phòng giao dịch Sa Đéc')
            ->assertSee('12 Nguyễn Huệ, Đồng Tháp')
            ->assertSee('0277 123 4567')
            ->assertSee('Phòng Khách hàng')
            ->assertSee('Giao dịch viên')
            ->assertSee('Quyền và bảo mật')
            ->assertSee('Vai trò đảm nhiệm')
            ->assertSee('Trạng thái')
            ->assertDontSee('Vai trò văn thư')
            ->assertSee('Đổi mật khẩu');
    }

    public function test_user_can_update_phone_citizen_id_and_avatar_without_changing_protected_fields(): void
    {
        $avatar = UploadedFile::fake()->image('avatar.jpg', 320, 320)->size(180);

        $response = $this->withSession($this->sessionData())->put('/profile', [
            'name' => 'Nguyễn Văn B (Attempted)',
            'employee_code' => 'NV-FORGED',
            'phone_no' => '0987654321',
            'citizen_id' => '087095001234',
            'avatar' => $avatar,
            'branch_id' => 999,
            'role_id' => '1',
            'document_role' => 'clerk',
            'email' => 'changed@example.test',
        ]);

        $response->assertRedirect(route('profile.show'))
            ->assertSessionHas('success')
            ->assertSessionHas('user_has_avatar', true);

        $user = User::findOrFail(4);
        $this->assertSame('Nguyễn Văn A', $user->name);
        $this->assertSame('0987654321', $user->phone_no);
        $this->assertSame('087095001234', $user->citizen_id);
        $this->assertSame('a@example.test', $user->email);
        $this->assertSame(1, $user->branch_id);
        $this->assertSame('2', $user->role_id);
        $this->assertSame('user', $user->document_role);
        $this->assertNotNull($user->avatar_path);
        $this->assertStringStartsWith('user-avatars/4/', $user->avatar_path);
        Storage::disk('local')->assertExists($user->avatar_path);

        $this->withSession($this->sessionData())
            ->get('/profile/avatar')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_replacing_or_removing_avatar_cleans_up_the_old_private_file(): void
    {
        $oldPath = 'user-avatars/4/old.jpg';
        Storage::disk('local')->put($oldPath, 'old-avatar');
        User::findOrFail(4)->update(['avatar_path' => $oldPath]);

        $this->withSession($this->sessionData())->put('/profile', [
            'avatar' => UploadedFile::fake()->image('new.png', 300, 300),
        ])->assertRedirect(route('profile.show'));

        $newPath = User::findOrFail(4)->avatar_path;
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);

        $this->withSession($this->sessionData())->put('/profile', [
            'remove_avatar' => '1',
        ])->assertRedirect(route('profile.show'));

        $this->assertNull(User::findOrFail(4)->avatar_path);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_avatar_validation_rejects_non_image_and_oversized_files(): void
    {
        $this->withSession($this->sessionData())->put('/profile', [
            'avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'text/plain'),
        ])->assertSessionHasErrors('avatar');

        $this->withSession($this->sessionData())->put('/profile', [
            'avatar' => UploadedFile::fake()->image('large.jpg', 300, 300)->size(2100),
        ])->assertSessionHasErrors('avatar');

        $this->assertNull(User::findOrFail(4)->avatar_path);
    }

    public function test_user_phone_no_validation_rejects_invalid_format(): void
    {
        $this->withSession($this->sessionData())->put('/profile', [
            'phone_no' => 'abc1234',
        ])->assertSessionHasErrors('phone_no');
    }

    public function test_password_change_ignores_a_forged_user_id_and_updates_only_the_session_user(): void
    {
        DB::table('users')->insert([
            'id' => 5, 'name' => 'Người dùng khác', 'email' => 'other@example.test',
            'password' => bcrypt('other-secret'), 'role_id' => '2', 'document_role' => 'user',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withSession($this->sessionData())->post('/reset_password_user', [
            'user_id' => 5,
            'old_password' => 'secret1',
            'password' => 'new-secret',
            'repassword' => 'new-secret',
        ])->assertSessionHas('message');

        $this->assertTrue(Hash::check('new-secret', User::findOrFail(4)->password));
        $this->assertTrue(Hash::check('other-secret', User::findOrFail(5)->password));
    }

    public function test_user_profile_displays_assigned_roles_without_icons_and_includes_clerk_under_assigned_roles(): void
    {
        User::findOrFail(4)->update([
            'role_id' => '2',
            'document_role' => 'clerk',
        ]);

        $response = $this->withSession($this->sessionData())
            ->get('/profile')
            ->assertOk();

        // Kiểm tra hiển thị cả Nhân viên và Văn thư chi nhánh trong Vai trò đảm nhiệm
        $response->assertSee('Vai trò đảm nhiệm');
        $response->assertSee('Nhân viên');
        $response->assertSee('Văn thư chi nhánh');
        $response->assertDontSee('Vai trò văn thư');

        // Đảm bảo không có icon trong huy hiệu vai trò
        $response->assertDontSee('fa-user-shield mr-1');
        $response->assertDontSee('fa-user-check mr-1');
        $response->assertDontSee('fa-file-signature mr-1');
        $response->assertDontSee('fa-laptop-code mr-1');
        $response->assertDontSee('fa-user-tie mr-1');
    }

    /** @return array<string, mixed> */
    private function sessionData(): array
    {
        return [
            'user_id' => 4,
            'user_name' => 'Nguyễn Văn A',
            'user_email' => 'a@example.test',
            'user_role' => '2',
            'UserBranchId' => null,
        ];
    }
}
