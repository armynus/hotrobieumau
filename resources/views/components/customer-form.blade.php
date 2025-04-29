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
                <button type="button" class="btn btn-secondary" id="close_button" data-dismiss="modal">{{ $closeText }}</button>
                <button type="submit" form="{{ $formId }}" class="btn btn-primary" id="{{ $submitId }}" >{{ $submitText }}</button>
            </div>
        </div>
    </div>
</div>
