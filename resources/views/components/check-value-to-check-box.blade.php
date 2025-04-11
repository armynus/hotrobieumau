{{-- <div class="checkbox-wrapper">
    <div class="checkbox-group">
        @foreach ($options as $key => $value)
            <label class="checkbox-inline">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" 
                    {{ in_array($key, (array) $selected) ? 'checked' : '' }} 
                    {{ $required ? 'required' : '' }}> 
                {{ $key }}
            </label>
        @endforeach
       
    </div>
</div> --}}

<div class="checkbox-wrapper">
    <div class="checkbox-group">
        @foreach ($options as $key => $value)
            @if (is_array($value))
                <div class="checkbox-row">
                    <span class="checkbox-label">{{ $key }}:</span>
                    <div class="checkbox-items">
                        @foreach ($value as $subKey => $subValue)
                            <label class="checkbox-inline">
                                <input type="checkbox" name="{{ $name }}" value="{{ $subValue }}"
                                    {{ in_array($subKey, (array) $selected) ? 'checked' : '' }}
                                    {{ $required ? 'required' : '' }}>
                                {{ $subKey }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <label class="checkbox-inline">
                    <input type="checkbox" name="{{ $name }}" value="{{$value}}"
                        {{ in_array($value, (array) $selected) ? 'checked' : '' }}
                        {{ $required ? 'required' : '' }}>
                    {{ $key }}
                </label>
            @endif
        @endforeach
    </div>
</div>


