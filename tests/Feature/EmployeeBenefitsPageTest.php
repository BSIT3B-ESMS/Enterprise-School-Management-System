<?php

beforeEach(fn () => $this->actingAs(employeeUser()));

it('serves the benefits page with the sidebar Benefits item current', function () {
    $this->get(route('employee.benefits'))
        ->assertOk()
        ->assertSee('data-page="portal-benefits"', escape: false)
        ->assertSee('aria-current="page" href="'.route('employee.benefits').'"', escape: false);
});

it('has a yours section, an other benefits section, and the empty states', function () {
    $this->get(route('employee.benefits'))
        ->assertSeeInOrder([
            'Benefits',
            'data-portal-missing', 'Employee record not found',
            'data-benefits-none', 'No benefits available',
            'Your benefits', 'data-benefits-yours-count', 'data-benefits-yours>',
            'data-benefits-yours-empty', 'No benefits included yet',
            'Other benefits', 'data-benefits-other>',
        ], escape: false);
});

it('shows nothing about pay or other employees on the benefits page', function () {
    $this->get(route('employee.benefits'))
        ->assertDontSee('salary', escape: false)
        ->assertDontSee('Salary', escape: false)
        ->assertDontSee('href="'.route('employees.index').'"', escape: false)
        ->assertDontSee('data-employee-', escape: false);
});
