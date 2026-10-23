@props(['document', 'label'])

{{--
    A 201 file document slot on the employee and application forms. resources/js/employee-documents.js keeps only each
    chosen file's name and size with the record; files are not uploaded or stored. The browser's own file input is
    replaced by Choose Files and a line naming the files chosen (instead of "No file chosen"), because the input is
    cleared after each pick so the same file can be added again later.
--}}
<div class="col-12" data-document-field="{{ $document }}">
    <label for="employee-documents-{{ $document }}" class="form-label">{{ $label }}</label>
    <input type="file" class="visually-hidden" id="employee-documents-{{ $document }}" multiple
        aria-describedby="employee-documents-{{ $document }}-status" data-document-input>
    <div class="input-group document-picker">
        <label class="btn btn-outline-secondary mb-0" for="employee-documents-{{ $document }}" aria-hidden="true">Choose Files</label>
        <span class="form-control text-truncate text-body-secondary" id="employee-documents-{{ $document }}-status" aria-live="polite"
            data-document-status>No file chosen</span>
    </div>
    <ul class="list-group document-list mt-2" data-document-list></ul>
</div>
