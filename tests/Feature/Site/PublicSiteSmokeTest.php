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

it('renders the footer with all three social profiles and no newsletter band', function () {
    config(['social.facebook' => null, 'social.youtube' => 'https://www.youtube.com/@fifiawoto']);

    $this->get(route('about'))
        ->assertOk()
        ->assertDontSee('Stay close to the work.')
        ->assertDontSee('In memory of Madam Dadaa')
        ->assertSee(route('donate'))
        ->assertSee('hello@fifiawoto.org')
        ->assertSee('https://www.youtube.com/@fifiawoto', escape: false)
        ->assertSee('href="https://facebook.com"', escape: false)
        ->assertSee('aria-label="Instagram"', escape: false)
        ->assertSee(route('legal.privacy'));
});

it('renders the page breadcrumb with a home link, the current page and structured data', function () {
    $response = $this->get(route('about'))->assertOk();

    $response->assertSee('aria-label="Breadcrumb"', escape: false)
        ->assertSee('aria-current="page">About</span>', escape: false)
        ->assertSee('"@type":"BreadcrumbList"', escape: false)
        ->assertSee('"name":"About"', escape: false);
});
