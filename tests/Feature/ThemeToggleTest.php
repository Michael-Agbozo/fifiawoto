<?php

use App\Models\User;

it('applies the saved theme before first paint and offers a toggle on the public site', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("localStorage.getItem('theme')", false)
        ->assertSee('$store.theme.toggle()', false);
});

it('offers the theme toggle in the admin header', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('$store.theme.toggle()', false);
});

it('defines a dark variant for every role token views rely on', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $dark = str($css)->after(':root[data-theme="dark"]')->before('}')->toString();

    foreach (['surface', 'heading', 'heading-soft', 'label', 'cream-50', 'cream-100', 'cream-200', 'cream-300', 'ink-500', 'ink-700', 'ink-900'] as $token) {
        expect($dark)->toContain("--color-{$token}:");
    }

    // Constant tokens must never flip — they sit on navy strips and backdrops.
    foreach (['paper', 'paper-dim', 'night', 'brand-900'] as $token) {
        expect($dark)->not->toContain("--color-{$token}:");
    }
});

it('drives the appearance settings page from the shared theme store', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('$store.theme.preference()', false)
        ->assertDontSee("localStorage.getItem('appearance')", false);
});
