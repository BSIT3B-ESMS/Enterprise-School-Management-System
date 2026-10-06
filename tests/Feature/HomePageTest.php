<?php

it('marks the home navigation link as the current page', function () {
    $this->get(route('home'))
        ->assertSee('aria-current="page"', escape: false);
});
