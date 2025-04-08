<div class="checkbox-wrapper">
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
</div>