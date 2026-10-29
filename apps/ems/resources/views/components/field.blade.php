@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null])

{{-- A labelled text-style input that keeps the old value and shows the server's validation message. A name like rows[0][title] is looked up as rows.0.title. --}}
@php
    $key = str_replace(['[', ']'], ['.', ''], rtrim($name, ']'));
    $inputId = 'field-'.str_replace(['[', ']'], ['-', ''], $name);
@endphp
<label for="{{ $inputId }}" class="form-label{{ $required ? ' is-required' : '' }}">{{ $label }}</label>
<input type="{{ $type }}" class="form-control{{ $errors->has($key) ? ' is-invalid' : '' }}" id="{{ $inputId }}"
    name="{{ $name }}" value="{{ $type === 'password' ? '' : old($key, $value) }}" @required($required)
    @if ($hint) aria-describedby="{{ $inputId }}-hint" @endif {{ $attributes }}>
@if ($hint)
    <div class="form-text" id="{{ $inputId }}-hint">{{ $hint }}</div>
@endif
@error($key)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
