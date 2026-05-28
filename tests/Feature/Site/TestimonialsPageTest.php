<?php

use App\Models\Testimonial;

it('renders the testimonials page with header copy', function () {
    $this->get(route('testimonials'))
        ->assertOk()
        ->assertSee('Stories of Impact')
        ->assertSee('Real voices');
});

it('lists every testimonial in the wall', function () {
    Testimonial::factory()->create(['author_name' => 'Akua Volunteer', 'quote' => 'I felt seen for the first time in my life.']);
    Testimonial::factory()->create(['author_name' => 'Kofi Parent',   'quote' => 'The school supplies kept my children in class.']);

    $this->get(route('testimonials'))
        ->assertOk()
        ->assertSee('Akua Volunteer')
        ->assertSee('Kofi Parent');
});

it('links to the testimonials page from the footer', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('testimonials'), escape: false);
});

it('paginates the wall when more than 12 testimonials exist', function () {
    Testimonial::factory()->count(15)->create();

    $page1 = $this->get(route('testimonials'));
    $page1->assertOk();
    $page1->assertSee('Next', escape: false);
    $page1->assertSee('Showing', escape: false);

    $page2 = $this->get(route('testimonials', ['page' => 2]));
    $page2->assertOk();
    $page2->assertSee('Previous', escape: false);
});

it('does not show pagination when there are 12 or fewer testimonials', function () {
    Testimonial::factory()->count(5)->create();

    $this->get(route('testimonials'))
        ->assertOk()
        ->assertDontSee('Showing 1');
});
