<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalLabelId }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalLabelId }}">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <x-alert-message />

            <!-- Body -->
            <div class="modal-body">
                @csrf
                <input type="hidden" id="edit_sup_form_type_id" name="edit_sup_form_type_id">

                <div class="container-fluid">
                    <div class="row">
                        @foreach ($fields as $id => $label)
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="{{ $id }}">{{ $label }}</label>

                                    @if(isset($selectFields) && in_array($id, $selectFields))
                                        <select class="form-control" id="{{ $id }}" name="{{ $id }}">
                                            @foreach($selectOptions[$id] ?? [] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input 
                                            type="{{ in_array($id, $dateFields ?? []) ? 'date' : 'text' }}" 
                                            class="form-control" 
                                            id="{{ $id }}" 
                                            name="{{ $id }}" 
                                            placeholder="{{ $placeholders[$id] ?? '' }}" 
                                            value="{{ $values[$id] ?? '' }}"
                                            {{ in_array($id, $disabledFields ?? []) ? 'disabled' : '' }}
                                        >
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">{{ $closeText }}</button>
                <button type="submit" form="{{ $formId }}" class="btn btn-primary" id="{{ $submitId }}">{{ $submitText }}</button>
            </div>
        </div>
    </div>
</div>