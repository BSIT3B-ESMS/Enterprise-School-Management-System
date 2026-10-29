@props(['name', 'label', 'options', 'value' => null, 'placeholder' => null, 'required' => false, 'hint' => null])

{{--
    A labelled select. `options` is a list of value => label pairs, or a list of group => (value => label) pairs when
    `grouped` is set on the attributes.
--}}
@php($selected = (string) old($name, $value))
<label for="field-{{ $name }}" class="form-label{{ $required ? ' is-required' : '' }}">{{ $label }}</label>
<select class="form-select{{ $errors->has($name) ? ' is-invalid' : '' }}" id="field-{{ $name }}" name="{{ $name }}"
    @required($required) @if ($hint) aria-describedby="field-{{ $name }}-hint" @endif {{ $attributes->except('grouped') }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @if ($attributes->has('grouped'))
        @foreach ($options as $group => $groupOptions)
            <optgroup label="{{ $group }}">
                @foreach ($groupOptions as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </optgroup>
        @endforeach
    @else
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    @endif
</select>
@if ($hint)
    <div class="form-text" id="field-{{ $name }}-hint">{{ $hint }}</div>
@endif
@error($name)
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
