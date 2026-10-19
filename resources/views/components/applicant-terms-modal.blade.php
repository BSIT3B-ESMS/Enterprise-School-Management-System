{{--
    Terms and Conditions with the Privacy Notice, shown before an applicant creates an account
    (resources/js/applicant-terms.js). Accept and Continue stays disabled until the box is checked; Decline closes
    the modal without continuing. Acceptance is kept for this browser tab's session only.
--}}
<div class="modal fade" id="applicant-terms-modal" tabindex="-1" aria-labelledby="applicant-terms-modal-title"
    aria-describedby="applicant-terms-modal-intro" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false"
    data-terms-modal>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="applicant-terms-modal-title">Terms and Conditions</h2>
            </div>

            <div class="modal-body applicant-terms">
                <p id="applicant-terms-modal-intro">
                    Please read these terms and the privacy notice before you create an applicant account.
                </p>

                <section class="mb-4">
                    <h3 class="employee-form-section">Using the Applicant Portal</h3>
                    <ol class="mb-0">
                        <li>The Applicant Portal lets you create an account, apply for open positions, and follow your applications.</li>
                        <li>Give true, complete, and current information. False or misleading information may disqualify your application.</li>
                        <li>Keep your login details to yourself. You are responsible for what is done with your account.</li>
                        <li>Applying does not guarantee a screening, an interview, or a job offer. HR decides how each application moves forward.</li>
                        <li>Screening and interview schedules are set by HR and may change. Check your Calendar and Applications pages for the latest schedule.</li>
                    </ol>
                </section>

                <section class="mb-4">
                    <h3 class="employee-form-section">Privacy Notice</h3>
                    <p>
                        We collect the personal information you give on your account and application, such as your name,
                        contact details, birthdate, government ID numbers, and the documents you attach.
                    </p>
                    <ul>
                        <li><strong>Purpose:</strong> to assess your application, contact you, and schedule your screening and interview.</li>
                        <li><strong>Access:</strong> only the HR staff who handle recruitment can view your application.</li>
                        <li><strong>If you are hired:</strong> the details you submitted are used to start your employee record.</li>
                        <li><strong>Your rights:</strong> you can view and update your profile at any time from the Applicant Portal.</li>
                    </ul>
                    <p class="mb-0">
                        Personal information is handled in line with the Data Privacy Act of 2012 (Republic Act No. 10173).
                    </p>
                </section>

                <div class="alert alert-secondary small mb-0" role="note">
                    This is a demonstration system. Accounts and applications are sample data kept in this browser only.
                    Nothing is sent to a server, and no email or text messages are sent.
                </div>
            </div>

            <div class="modal-footer flex-column align-items-stretch gap-3">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="applicant-terms-agree" data-terms-agree>
                    <label class="form-check-label" for="applicant-terms-agree">
                        I have read and agree to the Terms and Conditions and the Privacy Notice.
                    </label>
                </div>

                <div class="d-flex justify-content-end flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-terms-decline>Decline</button>
                    <button type="button" class="btn btn-primary" disabled data-terms-accept>Accept and Continue</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast app-toast" role="status" aria-live="polite" aria-atomic="true" data-terms-declined-toast>
        <div class="d-flex">
            <div class="toast-body">You need to accept the Terms and Conditions to create an account.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>
