<?php

it('renders the privacy policy', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('Information we collect')
        ->assertSee('Your rights')
        ->assertSee('hello@fifiawoto.org');
});

it('renders the terms of service', function () {
    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSee('Terms of Service')
        ->assertSee('Acceptance')
        ->assertSee('Permitted use of the site')
        ->assertSee('Limitation of liability');
});

it('renders the cookie policy', function () {
    $this->get(route('legal.cookies'))
        ->assertOk()
        ->assertSee('Cookie Policy')
        ->assertSee('Strictly necessary')
        ->assertSee('How to manage cookies');
});

it('renders the disclaimer', function () {
    $this->get(route('legal.disclaimer'))
        ->assertOk()
        ->assertSee('Disclaimer')
        ->assertSee('Not professional advice')
        ->assertSee('Photography and likeness');
});

it('shows a Last updated date on every legal page', function (string $route) {
    $this->get(route($route))->assertSee('Last updated', escape: false);
})->with([
    'legal.privacy',
    'legal.terms',
    'legal.cookies',
    'legal.disclaimer',
]);

it('links to every legal page from the footer', function () {
    $response = $this->get(route('home'));

    foreach ([
        'Privacy Policy',
        'Terms of Service',
        'Cookie Policy',
        'Disclaimer',
    ] as $label) {
        $response->assertSee($label, escape: false);
    }

    foreach ([
        route('legal.privacy'),
        route('legal.terms'),
        route('legal.cookies'),
        route('legal.disclaimer'),
    ] as $href) {
        $response->assertSee($href, escape: false);
    }
});

it('cross-links between policies', function () {
    $this->get(route('legal.privacy'))
        ->assertSee(route('legal.cookies'), escape: false);

    $this->get(route('legal.terms'))
        ->assertSee(route('legal.privacy'), escape: false)
        ->assertSee(route('legal.disclaimer'), escape: false);
});
