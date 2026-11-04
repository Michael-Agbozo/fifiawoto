<?php

use App\Models\User;

it('shows French after switching and remembers the choice', function () {
    $this->get(route('about'))->assertOk()->assertSee('<html lang="en">', false)->assertSee('Our Legacy');

    $this->from(route('about'))->post(route('language.switch', 'fr'))->assertRedirect(route('about'));

    $this->get(route('about'))
        ->assertSee('<html lang="fr">', false)
        ->assertSee('Notre héritage')
        ->assertDontSee('Our Legacy');

    $this->from(route('about'))->post(route('language.switch', 'en'));
    $this->get(route('about'))->assertSee('Our Legacy');
});

it('follows the browser language until the visitor chooses', function () {
    $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9'])->get(route('home'))->assertSee('<html lang="fr">', false);
    $this->withHeaders(['Accept-Language' => 'de-DE'])->get(route('home'))->assertSee('<html lang="en">', false);
});

it('rejects unknown languages', function () {
    $this->post('/language/de')->assertNotFound();
});

it('keeps staff in English', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->withSession(['locale' => 'fr'])
        ->get(route('home'))
        ->assertSee('<html lang="en">', false);
});

it('renders every public page in French', function (string $routeName) {
    $this->withSession(['locale' => 'fr'])->get(route($routeName))->assertOk()->assertSee('<html lang="fr">', false);
})->with(['home', 'about', 'sponsor-a-child', 'volunteer', 'apply-for-support', 'contact', 'donate', 'testimonials', 'media', 'events.index', 'legal.privacy']);

it('has no untranslated placeholders in the French file', function () {
    $translations = json_decode(file_get_contents(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($translations as $english => $french) {
        preg_match_all('/:v\d|:count/', $english, $expected);
        preg_match_all('/:v\d|:count/', $french, $actual);
        sort($expected[0]);
        sort($actual[0]);
        expect($actual[0])->toBe($expected[0], "Placeholder mismatch: {$english}");
    }
});
