<?php

beforeEach(fn () => $this->actingAs(hrHead()));

it('filters audit logs by a single date picker with no start or end date fields', function () {
    $this->get(route('audit.index'))
        ->assertSeeInOrder([
            '<label class="input-group-text" for="audit-date-filter">Date</label>',
            'type="date"', 'data-audit-filter="date"',
            'data-audit-clear>Clear Filters</button>',
        ], escape: false)
        ->assertSee('Show entries recorded on this date')
        ->assertDontSee('Start Date')
        ->assertDontSee('End Date');
});
