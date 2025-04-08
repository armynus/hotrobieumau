<select class="form-control" id="{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }}>
    @if ($placeholder)
        <option value="" disabled selected>{{ $placeholder }}</option>        
    @endif
    @foreach ($options as $key => $value)
        <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
            {{ $key }}
        </option>
    @endforeach
</select>
