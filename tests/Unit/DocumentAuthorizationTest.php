<?php

namespace Tests\Unit;

use App\Models\Branches;
use App\Models\Document;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DocumentAuthorizationTest extends TestCase
{
    public function test_document_model_keeps_only_document_metadata_fillable(): void
    {
        $fillable = (new Document())->getFillable();

        $this->assertContains('visibility', $fillable);
        $this->assertContains('document_code', $fillable);
        $this->assertNotContains('document_number', $fillable);
        $this->assertNotContains('summary', $fillable);
        $this->assertNotContains('file_path', $fillable);
        $this->assertNotContains('deleted_at', $fillable);
    }

    public function test_only_the_type_one_clerk_who_uploaded_the_document_can_edit_and_forward_it(): void
    {
        $typeOneBranch = new Branches(['branch_type' => 'type_1']);

        $uploader = new User(['document_role' => 'clerk']);
        $uploader->id = 10;
        $uploader->setRelation('branch', $typeOneBranch);

        $otherClerk = new User(['document_role' => 'clerk']);
        $otherClerk->id = 11;
        $otherClerk->setRelation('branch', $typeOneBranch);

        $document = new Document(['created_by' => 10]);

        $this->assertTrue($document->canBeEditedBy($uploader));
        $this->assertTrue($document->canBeTransferredToBranchBy($uploader));
        $this->assertTrue($document->canBeDeletedBy($uploader));
        $this->assertFalse($document->canBeEditedBy($otherClerk));
        $this->assertFalse($document->canBeTransferredToBranchBy($otherClerk));
        $this->assertFalse($document->canBeDeletedBy($otherClerk));
    }

    public function test_a_type_two_clerk_cannot_edit_or_forward_an_uploaded_document_to_another_branch(): void
    {
        $typeTwoBranch = new Branches(['branch_type' => 'type_2']);

        $clerk = new User(['document_role' => 'clerk']);
        $clerk->id = 20;
        $clerk->setRelation('branch', $typeTwoBranch);

        $document = new Document(['created_by' => 20]);

        $this->assertFalse($document->canBeEditedBy($clerk));
        $this->assertFalse($document->canBeTransferredToBranchBy($clerk));
        $this->assertFalse($document->canBeDeletedBy($clerk));
    }

    public function test_a_type_two_clerk_can_distribute_only_a_document_received_by_their_branch(): void
    {
        $typeTwoBranch = new Branches(['branch_type' => 'type_2']);

        $clerk = new User(['document_role' => 'clerk', 'branch_id' => 31]);
        $clerk->setRelation('branch', $typeTwoBranch);

        $incomingTransfers = new class
        {
            private int $targetBranchId = 0;

            public function where(string $column, int $value): self
            {
                if ($column === 'to_branch_id') {
                    $this->targetBranchId = $value;
                }

                return $this;
            }

            public function exists(): bool
            {
                return $this->targetBranchId === 31;
            }
        };

        $document = $this->getMockBuilder(Document::class)
            ->onlyMethods(['transfers'])
            ->getMock();
        $document->method('transfers')->willReturn($incomingTransfers);

        $this->assertTrue($document->canBeDistributedToDepartmentBy($clerk));

        $clerk->branch_id = 32;
        $this->assertFalse($document->canBeDistributedToDepartmentBy($clerk));
    }
}
