@props(['document', 'label'])

{{--
    A 201 file document slot on the employee form. resources/js/employee-form.js keeps only each
    chosen file's name and size with the record; files are not uploaded or stored.
--}}
<div class="col-12" data-document-field="{{ $document }}">
    <label for="employee-documents-{{ $document }}" class="form-label">{{ $label }}</label>
    <input type="file" class="form-control" id="employee-documents-{{ $document }}" multiple data-document-input>
    <ul class="list-group document-list mt-2" data-document-list></ul>
</div>
