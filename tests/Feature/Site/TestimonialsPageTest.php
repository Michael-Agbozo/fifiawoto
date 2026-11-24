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

it('plays a YouTube testimonial in a privacy-friendly player', function () {
    Testimonial::factory()->create(['author_name' => 'Akua', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

    $this->get(route('testimonials'))->assertOk()
        ->assertSee('Watch Akua’s story', escape: false)
        ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', escape: false);
});

it('links out to other https videos and ignores unsafe links', function () {
    Testimonial::factory()->create(['author_name' => 'Kofi', 'video_url' => 'https://vimeo.com/123456']);
    Testimonial::factory()->create(['author_name' => 'Esi', 'video_url' => 'javascript:alert(1)']);

    $this->get(route('testimonials'))->assertOk()
        ->assertSee('href="https://vimeo.com/123456"', escape: false)
        ->assertSee('Watch Kofi’s story', escape: false)
        ->assertDontSee('Watch Esi’s story', escape: false)
        ->assertDontSee('javascript:alert', escape: false);
});

it('shows the video button on featured testimonials on the home page', function () {
    Testimonial::factory()->featured()->create(['author_name' => 'Ama', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ']);

    $this->get(route('home'))->assertOk()->assertSee('Watch Ama’s story', escape: false);
});
