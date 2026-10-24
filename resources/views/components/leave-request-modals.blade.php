@props(['scope' => 'hr'])

@php($isHr = $scope === 'hr')

{{--
    A leave request's details, and the confirmation of what can be done with it, for resources/js/leave-requests.js.
    HR (scope="hr") approves a Pending request (remarks optional) or rejects it (a reason is required, then confirmed);
    the employee (scope="own") cancels their own Pending request after confirming. Nothing else changes a status.
--}}
<div class="modal fade" id="leave-details-modal" tabindex="-1" aria-labelledby="leave-details-modal-title" aria-hidden="true"
    data-leave-details-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="leave-details-modal-title" data-leave-details-title></h2>
                    <p class="small text-body-secondary mb-0" data-leave-details-subtitle></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger d-none" role="alert" data-leave-details-error></div>
                <div data-leave-details-body></div>
            </div>

            <div class="modal-footer" data-leave-details-actions></div>
        </div>
    </div>
</div>

@if ($isHr)
    {{-- Approve (remarks optional) or Reject (reason required, then a second confirmation). --}}
    <div class="modal fade" id="leave-decision-modal" tabindex="-1" role="alertdialog" aria-labelledby="leave-decision-modal-title"
        aria-describedby="leave-decision-modal-summary" aria-hidden="true" data-leave-decision-modal>
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" novalidate data-leave-decision-form>
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="leave-decision-modal-title" data-leave-decision-title></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body d-grid gap-3">
                    <div class="alert alert-danger mb-0 d-none" role="alert" data-leave-decision-error></div>
                    <p class="mb-0" id="leave-decision-modal-summary" data-leave-decision-summary></p>

                    <div data-leave-decision-step="enter">
                        <label for="leave-decision-remarks" class="form-label" data-leave-decision-label></label>
                        <textarea class="form-control" id="leave-decision-remarks" name="remarks" rows="3" maxlength="500"
                            aria-describedby="leave-decision-remarks-error leave-decision-remarks-hint"></textarea>
                        <div class="invalid-feedback" id="leave-decision-remarks-error"></div>
                        <div class="form-text" id="leave-decision-remarks-hint" data-leave-decision-hint></div>
                    </div>

                    <div class="alert alert-warning mb-0 d-none" role="status" data-leave-decision-step="confirm">
                        <strong class="d-block mb-1">Reject this request?</strong>
                        <span data-leave-decision-confirm-text></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-secondary d-none" data-leave-decision-back>Go Back</button>
                    <button type="submit" class="btn" data-leave-decision-submit></button>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="modal fade" id="leave-cancel-modal" tabindex="-1" role="alertdialog" aria-labelledby="leave-cancel-modal-title"
        aria-describedby="leave-cancel-modal-body" aria-hidden="true" data-leave-cancel-modal>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="leave-cancel-modal-title">Cancel leave request</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" role="alert" data-leave-cancel-error></div>
                    <p class="mb-0" id="leave-cancel-modal-body" data-leave-cancel-message></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Request</button>
                    <button type="button" class="btn btn-danger" data-leave-cancel-confirm>Cancel Request</button>
                </div>
            </div>
        </div>
    </div>
@endif
