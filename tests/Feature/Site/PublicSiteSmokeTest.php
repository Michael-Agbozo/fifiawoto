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
    'apply-for-support',
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

it('renders the footer with a working newsletter form and only configured social profiles', function () {
    config(['social.facebook' => null, 'social.youtube' => 'https://www.youtube.com/@fifiawoto']);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Stay close to the work.')
        ->assertSee('Compassion is a legacy that never fades.')
        ->assertSee(route('donate'))
        ->assertSee('hello@fifiawoto.org')
        ->assertSee('wire:submit="subscribe"', escape: false)
        ->assertSee('https://www.youtube.com/@fifiawoto', escape: false)
        ->assertDontSee('href="https://facebook.com"', escape: false)
        ->assertSee(route('legal.privacy'));
});
