{{-- The HR event form, for a new event or to edit one (the script sets the action when editing). --}}
<form method="post" @isset($action) action="{{ $action }}" @endisset class="row g-2 mt-1" data-form="event-{{ $formId }}" novalidate>
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="col-12"><label class="form-label is-required" for="ev-{{ $formId }}-title">Title</label><input class="form-control" type="text" id="ev-{{ $formId }}-title" name="title" required maxlength="150"></div>
    <div class="col-md-6">
        <label class="form-label" for="ev-{{ $formId }}-type">Type</label>
        <select class="form-select" id="ev-{{ $formId }}-type" name="type">@foreach ($eventTypes as $value => $label) <option value="{{ $value }}">{{ $label }}</option> @endforeach</select>
    </div>
    <div class="col-md-6"><label class="form-label is-required" for="ev-{{ $formId }}-date">Date</label><input class="form-control" type="date" id="ev-{{ $formId }}-date" name="date" required></div>
    <div class="col-md-6"><label class="form-label is-required" for="ev-{{ $formId }}-start">Start</label><input class="form-control" type="time" id="ev-{{ $formId }}-start" name="start_time" value="09:00" required></div>
    <div class="col-md-6"><label class="form-label" for="ev-{{ $formId }}-end">End</label><input class="form-control" type="time" id="ev-{{ $formId }}-end" name="end_time"></div>
    <div class="col-12"><label class="form-label" for="ev-{{ $formId }}-venue">Venue</label><input class="form-control" type="text" id="ev-{{ $formId }}-venue" name="venue" maxlength="150"></div>
    <div class="col-12"><label class="form-label" for="ev-{{ $formId }}-notes">Notes</label><textarea class="form-control" id="ev-{{ $formId }}-notes" name="notes" rows="2" maxlength="1000"></textarea></div>
    <div class="col-12 form-check ms-1"><input class="form-check-input" type="checkbox" id="ev-{{ $formId }}-everyone" name="for_everyone" value="1"><label class="form-check-label" for="ev-{{ $formId }}-everyone">Show on the Employee calendar</label></div>
    <div class="col-12 d-flex justify-content-end"><button type="submit" class="btn btn-primary">Save Event</button></div>
</form>
