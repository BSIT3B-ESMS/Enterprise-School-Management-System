<?php

it('renders the audit log heading, search, filters, and sortable table headers', function () {
    $this->get(route('audit.index'))
        ->assertSeeInOrder(['Audit Logs', 'Every sign-in and data change, newest first.'])
        ->assertSeeInOrder([
            'data-audit-search', 'data-audit-filter="action"', 'data-audit-filter="recordType"',
        ], escape: false)
        ->assertSeeInOrder([
            'data-sort-key="at"', 'Date and Time',
            'data-sort-key="user"', 'User',
            'data-sort-key="action"', 'Action',
            'data-sort-key="entity"', 'Affected Record',
            'Details</th>',
        ], escape: false)
        ->assertDontSee('Action</th>', escape: false);
});

it('renders a read-only details modal without edit or delete actions', function () {
    $this->get(route('audit.index'))
        ->assertSee('id="audit-details-modal"', escape: false)
        ->assertSee('data-bs-dismiss="modal">Close</button>', escape: false)
        ->assertDontSee('data-audit-edit', escape: false)
        ->assertDontSee('data-audit-delete', escape: false);
});

it('marks the audit logs sidebar link as the current page', function () {
    $this->get(route('audit.index'))
        ->assertSee('aria-current="page" href="'.route('audit.index').'"', escape: false)
        ->assertDontSee('aria-current="page" href="'.route('recruitment.index').'"', escape: false);
});

it('links audit logs from the sidebar and the notifications menu with nothing left coming soon', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('audit.index').'"', escape: false)
        ->assertSeeInOrder(['data-notifications', 'href="'.route('audit.index').'"', 'View all activity'], escape: false)
        ->assertDontSee('data-coming-soon', escape: false);
});

it('marks both logout links so the sign-out is recorded', function () {
    $this->get(route('dashboard'))
        ->assertSee('href="'.route('home').'" data-logout>', escape: false)
        ->assertSee('class="app-sidebar-logout" href="'.route('home').'" data-logout', escape: false);
});
