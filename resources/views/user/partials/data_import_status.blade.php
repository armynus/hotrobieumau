@if($activeImport)
    @php
        $isActive = in_array($activeImport->status, ['queued', 'running'], true);
        $totalRows = (int) ($activeImport->total_rows ?? 0);
        $processedRows = (int) $activeImport->processed_rows;
        $progress = $totalRows > 0 ? min(100, (int) round($processedRows * 100 / $totalRows)) : 0;
    @endphp
    <div class="alert {{ $activeImport->status === 'failed' ? 'alert-danger' : ($activeImport->status === 'completed' ? 'alert-success' : 'alert-info') }} mx-3 mt-3 mb-0"
         role="status"
         data-import-status
         data-status-url="{{ route('data-imports.show', $activeImport) }}">
        <div class="d-flex align-items-start justify-content-between">
            <div>
                <strong data-import-title>
                    {{ $activeImport->status === 'completed' ? 'Nhập dữ liệu hoàn tất' : ($activeImport->status === 'failed' ? 'Nhập dữ liệu chưa thành công' : 'Đang nhập dữ liệu nền') }}
                </strong>
                <div class="small text-break">{{ $activeImport->original_name }}</div>
            </div>
            <span class="badge badge-light ml-3" data-import-percent>{{ $progress }}%</span>
        </div>
        <div class="progress mt-2" style="height: 8px;">
            <div class="progress-bar {{ $isActive ? 'progress-bar-striped progress-bar-animated' : '' }}"
                 role="progressbar"
                 style="width: {{ $progress }}%"
                 aria-valuenow="{{ $progress }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 data-import-progress></div>
        </div>
        <div class="small mt-2" data-import-detail>
            Đã xử lý {{ number_format($processedRows, 0, ',', '.') }}{{ $totalRows > 0 ? ' / '.number_format($totalRows, 0, ',', '.').' dòng' : ' dòng' }}.
            Thêm {{ number_format($activeImport->inserted_rows, 0, ',', '.') }}, cập nhật {{ number_format($activeImport->updated_rows, 0, ',', '.') }}, bỏ qua {{ number_format($activeImport->skipped_rows, 0, ',', '.') }}.
        </div>
    </div>
@endif

@once
    @push('scripts')
        <script src="{{ asset('js/user/data-import-status.js') }}"></script>
    @endpush
@endonce
