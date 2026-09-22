<?php

namespace Tests\Unit;

use App\Services\FormWorkspaceService;
use PHPUnit\Framework\TestCase;

class FormWorkspaceGroupingTest extends TestCase
{
    public function test_explicit_group_overrides_field_code_inference_and_order_is_applied(): void
    {
        $groups = FormWorkspaceService::groups([
            'CCCD_custom' => ['field_name' => 'Mã riêng', 'content_group' => 'customer', 'display_order' => 20],
            'nameloc' => ['field_name' => 'Tên khách hàng', 'content_group' => 'customer', 'display_order' => 10],
            'SoTien' => ['field_name' => 'Số tiền', 'content_group' => 'transaction', 'display_order' => 30],
            'branch' => ['field_name' => 'Chi nhánh', 'content_group' => 'preparation', 'display_order' => 10],
        ]);

        $this->assertSame(['nameloc', 'CCCD_custom'], array_keys($groups['customer']['fields']));
        $this->assertArrayNotHasKey('CCCD_custom', $groups['identity']['fields'] ?? []);
        $this->assertSame(['branch'], array_keys($groups['preparation']['fields']));
    }

    public function test_legacy_fields_still_receive_a_safe_inferred_group(): void
    {
        $groups = FormWorkspaceService::groups([
            'identity_no' => ['field_name' => 'Số giấy tờ'],
            'idxacno' => ['field_name' => 'Số tài khoản'],
            'unknown_request' => ['field_name' => 'Yêu cầu khác'],
        ]);

        $this->assertArrayHasKey('identity_no', $groups['identity']['fields']);
        $this->assertArrayHasKey('idxacno', $groups['account']['fields']);
        $this->assertArrayHasKey('unknown_request', $groups['transaction']['fields']);
    }
}
