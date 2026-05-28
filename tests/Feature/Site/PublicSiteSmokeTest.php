<?php

it('renders every public page with the brand name', function (string $route) {
    $response = $this->get(route($route));

    $response->assertOk();
    $response->assertSee('Fifiawoto');
})->with([
    'home',
    'about',
    'events.index',
    'volunteer',
    'contact',
    'donate',
]);

it('renders the home hero copy', function () {
    $this->get(route('home'))
        ->assertSee('Empowering')
        ->assertSee('New Beginnings')
        ->assertSee('Get In Touch');
});

it('exposes the primary navigation links on every page', function (string $route) {
    $response = $this->get(route($route));

    foreach (['Home', 'About', 'Events', 'Volunteer', 'Contact', 'Donate'] as $label) {
        $response->assertSee($label, escape: false);
    }
})->with([
    'home',
    'about',
    'events.index',
    'volunteer',
    'contact',
]);
