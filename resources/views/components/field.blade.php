@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])

{{-- A labelled text-style input that keeps the old value and shows the server's validation message. --}}
<label for="field-{{ $name }}" class="form-label{{ $required ? ' is-required' : '' }}">{{ $label }}</label>
<input type="{{ $type }}" class="form-control{{ $errors->has($name) ? ' is-invalid' : '' }}" id="field-{{ $name }}"
    name="{{ $name }}" value="{{ old($name, $value) }}" @required($required)
    @if ($hint) aria-describedby="field-{{ $name }}-hint" @endif {{ $attributes }}>
@if ($hint)
    <div class="form-text" id="field-{{ $name }}-hint">{{ $hint }}</div>
@endif
@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
