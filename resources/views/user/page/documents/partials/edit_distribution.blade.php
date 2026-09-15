@once
@push('styles')
<style>
    .document-distribution-modal .modal-content > form { display: flex; flex-direction: column; min-height: 0; max-height: 100%; overflow: hidden; }
    .document-distribution-modal .modal-body { overflow-y: auto; }
    .document-distribution-modal .modal-header, .document-distribution-modal .modal-footer { flex-shrink: 0; }
</style>
@endpush
@endonce
<section class="document-edit-distribution border rounded p-3 mt-3 mb-3" aria-label="Phân phối và phạm vi xem">
    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-share-alt mr-1" aria-hidden="true"></i> Phân phối và phạm vi xem</h6>
    <input type="hidden" name="sync_recipients" value="1">
    <div class="form-group">
        <label for="{{ $distributionPrefix }}Visibility" class="font-weight-bold">Mức độ công khai</label>
        <select id="{{ $distributionPrefix }}Visibility" name="is_public_level" class="form-control" aria-describedby="{{ $distributionPrefix }}VisibilityHelp" required>
            <option value="private">Riêng Tư</option>
            <option value="normal">Bình Thường</option>
            <option value="public">Công Khai</option>
        </select>
        <small id="{{ $distributionPrefix }}VisibilityHelp" class="form-text text-muted distribution-help" aria-live="polite"></small>
    </div>
    @include('user.page.documents.partials.local_recipients')
    <div class="distribution-branches">
        <div class="font-weight-bold text-primary mb-2"><i class="fas fa-building mr-1" aria-hidden="true"></i> Chi nhánh loại II nhận văn bản</div>
        <small class="d-block text-muted mb-2">Gửi cho văn thư chi nhánh nhận để chuyển tiếp người/phòng ban trong chi nhánh đó.</small>
        <div class="border rounded p-3 bg-light" style="max-height:220px;overflow-y:auto;">
            @if($type2Branches->isNotEmpty())
                <label class="d-flex align-items-center border-bottom pb-2"><input type="checkbox" class="mr-2 distribution-all-branches">Chọn tất cả chi nhánh</label>
            @endif
            @forelse($type2Branches as $branch)
                <label class="d-flex align-items-start mb-2"><input type="checkbox" name="to_branch_ids[]" value="{{ $branch->id }}" class="mr-2 mt-1"><span>{{ $branch->branch_name }}</span></label>
            @empty
                <small class="text-muted">Chưa có chi nhánh loại II đang hoạt động.</small>
            @endforelse
        </div>
    </div>
    <div class="small text-muted mt-3"><i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Bỏ tích và lưu để thu hồi quyền xem. Bỏ chi nhánh sẽ thu hồi cả các nơi được chi nhánh đó chuyển tiếp. Lịch sử vẫn giữ; file đã tải về không thể thu hồi.</div>
</section>
