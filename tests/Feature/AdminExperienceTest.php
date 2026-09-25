<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminExperienceTest extends TestCase
{
    public function test_dashboard_uses_the_admin_shell_and_real_quick_links(): void
    {
        $this->withSession(['admin_id' => 1, 'admin_name' => 'Quản trị thử nghiệm']);

        $html = view('admin.dashboard', [
            'branch_count' => 3,
            'user_count' => 12,
            'form_count' => 8,
            'field_count' => 25,
        ])->render();

        $this->assertStringContainsString('admin-experience.css', $html);
        $this->assertStringContainsString('css/shared/shell-theme.css', $html);
        $this->assertStringContainsString('Tổng quan hệ thống', $html);
        $this->assertStringContainsString('Quản trị thử nghiệm', $html);
        $this->assertStringContainsString(route('admin_department_list'), $html);
        $this->assertStringContainsString(route('admin.it_support.manage'), $html);
        $this->assertStringNotContainsString('Nhập số văn bản hoặc trích yếu nội dung muốn tìm', $html);
    }
}
