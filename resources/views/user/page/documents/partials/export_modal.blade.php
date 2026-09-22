<div class="modal fade" id="documentExportModal" tabindex="-1" role="dialog" aria-labelledby="documentExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <form id="documentExportForm" method="POST" action="{{ route('documents_export') }}" data-document-export-form>
                @csrf
                <input type="hidden" name="background" value="1">
                <div class="modal-header document-export-header text-white">
                    <div class="d-flex align-items-center">
                        <span class="document-export-icon mr-3"><i class="fas fa-file-excel"></i></span>
                        <div>
                            <h5 class="modal-title mb-1" id="documentExportModalLabel">Xuất sổ văn bản</h5>
                            <div class="small text-white-50">File Excel đúng cấu trúc sổ văn thư</div>
                        </div>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Đóng"><span>&times;</span></button>
                </div>
                <div class="modal-body p-4">
                    <label class="font-weight-bold text-gray-800 mb-2">Chọn loại sổ</label>
                    <div class="btn-group btn-group-toggle d-flex mb-4" data-toggle="buttons">
                        <label class="btn btn-outline-primary active flex-fill">
                            <input type="radio" name="direction" value="incoming" autocomplete="off" checked> Sổ văn bản đến
                        </label>
                        <label class="btn btn-outline-success flex-fill">
                            <input type="radio" name="direction" value="outgoing" autocomplete="off"> Sổ văn bản đi
                        </label>
                    </div>
                    <label class="font-weight-bold text-gray-800 mb-3">Chọn kỳ báo cáo</label>
                    <div class="row document-period-options mb-4">
                        @foreach([
                            'month' => ['calendar-alt', 'Theo tháng', 'Một tháng cụ thể'],
                            'quarter' => ['chart-pie', 'Theo quý', 'Ba tháng liên tiếp'],
                            'year' => ['calendar', 'Theo năm', 'Toàn bộ một năm'],
                        ] as $value => $option)
                        <div class="col-md-4 mb-2 mb-md-0">
                            <input type="radio" class="d-none export-period-radio" name="period_type"
                                id="export_period_{{ $value }}" value="{{ $value }}" @checked($value === 'month')>
                            <label class="document-period-card" for="export_period_{{ $value }}">
                                <i class="fas fa-{{ $option[0] }}"></i>
                                <span class="font-weight-bold">{{ $option[1] }}</span>
                                <small>{{ $option[2] }}</small>
                            </label>
                        </div>
                        @endforeach
                    </div>

                    <div class="document-export-selection rounded p-3 mb-3">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-5 mb-md-0">
                                <label for="exportYear">Năm</label>
                                <select class="form-control" name="year" id="exportYear" required>
                                    @for($year = now()->year + 1; $year >= 2000; $year--)
                                        <option value="{{ $year }}" @selected($year === now()->year)>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group col-md-7 mb-md-0" id="exportMonthGroup">
                                <label for="exportMonth">Tháng</label>
                                <select class="form-control" name="month" id="exportMonth">
                                    @for($month = 1; $month <= 12; $month++)
                                        <option value="{{ $month }}" @selected($month === now()->month)>Tháng {{ $month }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group col-md-7 mb-md-0 d-none" id="exportQuarterGroup">
                                <label for="exportQuarter">Quý</label>
                                <select class="form-control" name="quarter" id="exportQuarter">
                                    @for($quarter = 1; $quarter <= 4; $quarter++)
                                        <option value="{{ $quarter }}" @selected($quarter === now()->quarter)>Quý {{ $quarter }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border d-flex align-items-start mb-0">
                        <i class="fas fa-info-circle text-primary mt-1 mr-2"></i>
                        <div>
                            <div class="font-weight-bold text-gray-800" id="documentExportSummary"></div>
                            <div class="small text-muted">Chỉ xuất văn bản đã vào sổ của chi nhánh. Sổ đi tách hai sheet thông thường và quyết định. Mỗi lần xuất tối đa {{ number_format(config('documents.exports.max_rows', 20000)) }} văn bản.</div>
                        </div>
                    </div>
                    @include('user.page.documents.partials.export_status')
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success px-4" id="documentExportSubmit" data-document-export-submit>
                        <i class="fas fa-download mr-1"></i> Tải file Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
