<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Metadata của một văn bản; thông tin file thật nằm ở document_attachments.
 *
 * @property int $id Khóa chính.
 * @property string $direction Luồng văn bản: incoming (đến), outgoing (đi) hoặc unclassified (chưa phân loại).
 * @property string|null $registry_number Số vào sổ, chỉ dùng cho văn bản đến.
 * @property string|null $document_code Số và ký hiệu ghi trên văn bản.
 * @property string|null $title Tên loại và trích yếu; có thể trống với kho cũ chưa cập nhật.
 * @property int|null $document_type_id Loại văn bản trong danh mục document_types.
 * @property int|null $managing_branch_id Chi nhánh đang quản lý văn bản.
 * @property \Illuminate\Support\Carbon|null $issued_date Ngày ghi trên văn bản.
 * @property \Illuminate\Support\Carbon|null $received_date Ngày nhận văn bản đến.
 * @property \Illuminate\Support\Carbon|null $forwarded_date Ngày chuyển/phát hành văn bản.
 * @property string|null $issuing_agency Tác giả hoặc cơ quan gửi văn bản đến.
 * @property string|null $signer Người ký văn bản đi.
 * @property string|null $recipient Nơi, đơn vị hoặc người nhận văn bản.
 * @property string|null $archive_recipient Đơn vị hoặc người nhận bản lưu của văn bản đi.
 * @property int|null $copy_count Số lượng bản phát hành.
 * @property string|null $receipt_signature Thông tin ký nhận.
 * @property string|null $notes Ghi chú của văn bản.
 * @property string $priority Mức độ xử lý: normal, urgent hoặc very_urgent.
 * @property string $security_level Độ mật: normal, confidential, secret hoặc top_secret.
 * @property string $visibility Phạm vi xem: private, branch hoặc system.
 * @property int|null $created_by Văn thư đã đăng tải văn bản.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Document extends Model
{
    use HasFactory;

    public const DIRECTION_INCOMING = 'incoming';
    public const DIRECTION_OUTGOING = 'outgoing';
    public const DIRECTION_UNCLASSIFIED = 'unclassified';

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_BRANCH = 'branch';
    public const VISIBILITY_SYSTEM = 'system';

    protected $fillable = [
        // Phân loại và nhận diện văn bản.
        'direction',
        'registry_number',
        'document_code',
        'title',
        'document_type_id',

        // Chi nhánh sở hữu và phạm vi người dùng được phép xem.
        'managing_branch_id',
        'visibility',

        // Các mốc ngày dùng trong sổ văn bản đến/đi.
        'issued_date',
        'received_date',
        'forwarded_date',

        // Nội dung nghiệp vụ của sổ văn bản.
        'issuing_agency',
        'signer',
        'recipient',
        'archive_recipient',
        'copy_count',
        'receipt_signature',
        'notes',

        // Phân loại xử lý, bảo mật và người đăng tải.
        'priority',
        'security_level',
        'created_by',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'received_date' => 'date',
        'forwarded_date' => 'date',
        'copy_count' => 'integer',
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function managingBranch(): BelongsTo
    {
        return $this->belongsTo(Branches::class, 'managing_branch_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class, 'document_id');
    }

    public function transfers()
    {
        return $this->hasMany(DocumentTransfer::class, 'document_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(DocumentRead::class, 'document_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class, 'document_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentLog::class, 'document_id');
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function canBeEditedBy(User $user): bool
    {
        return $user->canUploadDocument() && (int) $this->created_by === (int) $user->id;
    }

    public function canBeTransferredToBranchBy(User $user): bool
    {
        return $this->canBeEditedBy($user);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->canBeEditedBy($user);
    }

    public function canBeDistributedToDepartmentBy(User $user): bool
    {
        if (!$user->isClerk() || $user->branch?->branch_type !== 'type_2') {
            return false;
        }

        return $this->transfers()
            ->where('to_branch_id', $user->branch_id)
            ->exists();
    }
}
