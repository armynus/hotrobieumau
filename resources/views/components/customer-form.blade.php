<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalLabelId }}" aria-hidden="true">
    <div class="modal-dialog  modal-xl">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalLabelId }}">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @csrf
                @if($submitId == 'updateCustomer' || $submitId =='updateAccount' || $submitId == 'updateFormType')
                    <input type="hidden" id="view_id" name="view_id">
                @endif
                <div class="container-fluid">
                    <div class="row">
                        @foreach ($fields as $id => $label)
                            <div class="col-md-6">
                                <div class="form-group">
                                    @if (in_array($id, ['gender', 'add_gender']))
                                        <label for="{{ $id }}">{{ $label }}</label>
                                        <select class="form-control" id="{{ $id }}" name="{{ $id }}">
                                            <option value="Nam">Nam</option>
                                            <option value="Nữ">Nữ</option>
                                        </select>
                                    @else
                                        <label for="{{ $id }}">{{ $label }}</label>
                                        <input type="{{ in_array($id, $dateFields) ? 'date' : 'text' }}" 
                                               class="form-control" 
                                               id="{{ $id }}" 
                                               name="{{ $id }}" 
                                               placeholder="" 
                                               {{ in_array($id, $disabledFields ?? []) ? 'disabled' : '' }}>
                                    @endif
                                </div>
                            </div>
                            @if (($loop->iteration % 2) == 0) <!-- Chia hàng sau mỗi 2 input -->
                                </div><div class="row">
                            @endif
                        @endforeach
                        
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="modal-footer">
                @if($submitId == 'addCustomer')
                    <span  class="btn btn-primary pasteClipboardBtn" id="pasteClipboardAddCusTomerBtn">
                        Dữ liệu Clipboard
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-fill" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M10 1.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5zm-5 0A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5v1A1.5 1.5 0 0 1 9.5 4h-3A1.5 1.5 0 0 1 5 2.5zm-2 0h1v1A2.5 2.5 0 0 0 6.5 5h3A2.5 2.5 0 0 0 12 2.5v-1h1a2 2 0 0 1 2 2V14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3.5a2 2 0 0 1 2-2"/>
                        </svg>
                    </span>
                @endif
                <button type="button" class="btn btn-secondary" id="close_button" data-dismiss="modal">{{ $closeText }}</button>
                <button type="submit" form="{{ $formId }}" class="btn btn-primary" id="{{ $submitId }}" >{{ $submitText }}</button>
            </div>
        </div>
    </div>
</div>
