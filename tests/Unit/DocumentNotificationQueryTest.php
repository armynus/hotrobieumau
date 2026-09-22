<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DocumentQueryService;
use Tests\TestCase;

class DocumentNotificationQueryTest extends TestCase
{
    public function test_archive_imports_can_be_excluded_from_the_unread_notification_query(): void
    {
        $user = new User([
            'branch_id' => 1,
            'document_role' => 'clerk',
        ]);
        $user->id = 3;

        $query = app(DocumentQueryService::class)->getDocumentsForUser($user, [
            'is_read' => false,
            'exclude_archive_imports' => true,
        ]);

        $this->assertStringContainsString('not exists', mb_strtolower($query->toSql()));
        $this->assertContains('archive_imported', $query->getBindings());
    }

    public function test_document_sorting_has_a_stable_id_tie_breaker(): void
    {
        $user = new User([
            'branch_id' => 1,
            'document_role' => 'clerk',
        ]);

        $sql = app(DocumentQueryService::class)->getDocumentsForUser($user, [
            'sort_by' => 'issued_date',
            'sort_dir' => 'desc',
        ])->toSql();

        $this->assertMatchesRegularExpression(
            '/order by .*issued_date.*desc, .*id.*desc/i',
            $sql
        );
    }

    public function test_incoming_and_outgoing_lists_use_their_ledger_dates(): void
    {
        $user = new User([
            'branch_id' => 1,
            'document_role' => 'clerk',
        ]);

        $incoming = app(DocumentQueryService::class)->getDocumentsForUser($user, [
            'direction' => 'incoming',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]);
        $outgoing = app(DocumentQueryService::class)->getDocumentsForUser($user, [
            'direction' => 'outgoing',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]);

        $this->assertStringContainsString('received_date', $incoming->toSql());
        $this->assertStringNotContainsString('date(', mb_strtolower($incoming->toSql()));
        $this->assertContains('incoming', $incoming->getBindings());
        $this->assertStringContainsString('forwarded_date', $outgoing->toSql());
        $this->assertStringNotContainsString('date(', mb_strtolower($outgoing->toSql()));
        $this->assertContains('outgoing', $outgoing->getBindings());
    }

    public function test_document_visibility_uses_one_explicit_scope_column(): void
    {
        $user = new User([
            'branch_id' => 1,
            'document_role' => 'clerk',
        ]);

        $query = app(DocumentQueryService::class)->getDocumentsForUser($user);
        $sql = mb_strtolower($query->toSql());

        $this->assertStringContainsString('visibility', $sql);
        $this->assertStringNotContainsString('is_public', $sql);
        $this->assertNotContains('system', $query->getBindings());
        $this->assertContains('normal', $query->getBindings());
        $this->assertContains('public', $query->getBindings());
    }

    public function test_keyword_filter_searches_only_the_document_code(): void
    {
        $user = new User([
            'branch_id' => 1,
            'document_role' => 'clerk',
        ]);

        $query = app(DocumentQueryService::class)->getDocumentsForUser($user, [
            'keyword' => 'KHCN',
        ]);
        $sql = mb_strtolower($query->toSql());

        $this->assertStringContainsString('document_code', $sql);
        $this->assertStringNotContainsString('title` like', $sql);
        $this->assertStringNotContainsString('issuing_agency` like', $sql);
        $this->assertContains('%KHCN%', $query->getBindings());
    }
}
