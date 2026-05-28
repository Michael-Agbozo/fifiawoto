# Design + style guide

Reference for designers and engineers extending the public site. The conventions below are encoded in `resources/css/app.css` (Tailwind v4 `@theme` block) and the reusable components under `resources/views/components/site/`.

---

## Brand palette

White surfaces · deep navy structure · red primary accent. Pulled from the foundation's reference site.

| Token            | Hex       | Used for                                    |
| ---------------- | --------- | ------------------------------------------- |
| `--color-cream-50`  | `#ffffff` | Default white surfaces, card bodies         |
| `--color-cream-100` | `#f9fafb` | Subtle off-white blocks (e.g. footer)       |
| `--color-cream-200` | `#f1f3f5` | The body "canvas" behind the framed cards   |
| `--color-cream-300` | `#e0e3e8` | Hairline borders, dividers                  |
| `--color-brand-50`  | `#eef0ff` | Lightest brand tint                         |
| `--color-brand-100` | `#d6daf2` | Card borders, subtle backgrounds            |
| `--color-brand-200` | `#a8aedb` | The lavender quote band on the about page   |
| `--color-brand-700` | `#03075e` | Body links, active states                   |
| `--color-brand-800` | `#000342` | Mid-tone navy                               |
| `--color-brand-900` | `#00044e` | Deep navy — headers, hero overlays, CTAs    |
| `--color-gold-300`  | `#ff7373` | Hover highlights on dark backgrounds        |
| `--color-gold-400`  | `#ff4d4d` | Accent on navy sections                     |
| `--color-gold-500`  | `#df0000` | **Primary red accent** — buttons, kickers   |
| `--color-gold-600`  | `#a30000` | Hover/active states for the primary accent  |
| `--color-ink-500`   | `#5a5a5a` | Body copy / secondary text                  |
| `--color-ink-700`   | `#2c2c2c` | Body emphasis                               |
| `--color-ink-900`   | `#000000` | Strong text                                 |

The `gold-*` keys are historical (when the brand was navy+gold). They're red now; renaming would churn every view, so the name stays.

---

## Typography

| Use                         | Family                | Weight     |
| --------------------------- | --------------------- | ---------- |
| Body, forms, buttons        | **Roboto** (`--font-sans`) | 400/500/600/700 |
| Headings h1–h6              | **Playfair Display** (`--font-serif`) — auto-applied via `@layer base` | 700 |
| Kickers / labels (uppercase) | Roboto                | 600 (semibold) |

Sizes per page surface:

| Element                  | Mobile        | Desktop                          |
| ------------------------ | ------------- | -------------------------------- |
| Hero H1                  | `text-4xl`    | `sm:text-5xl lg:text-6xl xl:text-[80px]` |
| Section H2               | `text-3xl`    | `sm:text-4xl lg:text-[52px]`     |
| Card H3                  | `text-xl`     | `sm:text-2xl`                    |
| Body                     | `text-sm`     | `sm:text-base`                   |
| Kicker / label           | `text-xs`     | uppercase, `tracking-[0.24em–0.32em]`, gold-500 |

---

## Layout — the framed card shell

The public site lives inside a **cream-200 canvas** with a **white rounded card** containing the page content. Defined in `resources/views/layouts/site.blade.php`:

```html
<body class="bg-cream-200">
    <div class="space-y-3 p-3 sm:space-y-4 sm:p-4 lg:space-y-6 lg:p-6">
        <header rounded-3xl />                  {{-- Sticky pill --}}
        <div class="overflow-clip rounded-3xl bg-white shadow-...">
            <main>{{ $slot }}</main>
            <footer />
        </div>
    </div>
</body>
```

Page heroes use an additional inset:

```html
<section class="mx-3 mt-3 rounded-3xl ... sm:mx-4 sm:mt-4 lg:mx-6 lg:mt-6">
```

so they float as their own rounded "card-within-card", with the white card surface visible around them.

`overflow-clip` (not `overflow-hidden`) preserves the **sticky header** scroll behaviour — `clip` doesn't create a scroll container.

---

## Page hero — `<x-site.page-hero>`

The canonical hero for inner pages. Full-bleed photo + navy wash + breadcrumb (top) + headline (bottom).

```blade
<x-site.page-hero
    title="About Us"
    breadcrumb="About"
    image="images/foundation/about/team-group.jpg"
    alt="The Fifiawoto Foundation team"
>
    Optional supporting copy that renders beneath the headline.
</x-site.page-hero>
```

- `title` (required) — H1
- `breadcrumb` — current-page label. The Home link is added automatically.
- `image` — path under `public/`
- `align` — `'left'` (default) or `'center'`
- Slot — optional `<p>` subtitle

Inherits the framed-card insets (`mx-3 mt-3 sm:mx-4 ...`), so it sits inside the white card with the cream frame visible around it.

---

## Animation system

Animations follow the **Elementor fadeIn family** (verified against the reference site's `data-settings` JSON). All defined in `resources/css/app.css`.

| Class           | Direction          | Use case                                       |
| --------------- | ------------------ | ---------------------------------------------- |
| `reveal` / `reveal-up` | rises from below  | Default — content, body copy, CTAs             |
| `reveal-down`   | drops from above   | Kickers / overlines / breadcrumbs              |
| `reveal-left`   | slides from left   | Left column in two-column blocks               |
| `reveal-right`  | slides from right  | Right column in two-column blocks              |
| `reveal-zoom`   | scales up          | Hero photos, featured images                   |
| `reveal-fade`   | opacity-only       | Quiet content; no movement                     |

**Duration**: 1800ms (slow + deliberate) with `cubic-bezier(0.16, 1, 0.3, 1)` easing.

**Travel distance**: 48px (`reveal*`) or `scale(0.88)` (`reveal-zoom`).

**Cascading** — stagger with `transition-delay`:

```blade
<div class="reveal-down">Kicker</div>
<h1 class="reveal" style="transition-delay: 120ms">Title</h1>
<p class="reveal" style="transition-delay: 240ms">Body</p>
<a class="reveal" style="transition-delay: 360ms">CTA</a>
```

**Reduced motion** — `prefers-reduced-motion: reduce` disables all reveals and counters; users with the OS preference see content instantly.

---

## Counter animation

```blade
<p data-counter="520"
   data-suffix="+"
   data-duration="3200">0+</p>
```

- `data-counter` — target number (integer)
- `data-prefix` — prepended to value (e.g. `$`)
- `data-suffix` — appended (e.g. `+`)
- `data-duration` — milliseconds (default 2800)

The IntersectionObserver in `resources/js/app.js` triggers the count-up when the element enters the viewport. Tabular-nums CSS prevents the digits from jiggling.

---

## Image fade-in

```blade
<img src="..." class="img-fade" loading="lazy">
```

Fades in with a 600ms transition once the underlying image has decoded. Pair with `loading="lazy"` for below-the-fold images.

---

## Modal — `<x-admin.modal>` (admin only)

Used by every admin Livewire form. Features:

- Cream canvas backdrop with `backdrop-blur-sm`
- Header (sticky, shrink-0) + scrollable body (overflow-y-auto)
- `max-h-[calc(100dvh-3rem)]` cap so the modal never exceeds viewport
- Bottom-sheet feel on mobile (`items-end` + `rounded-t-3xl`); centered desktop (`sm:items-center sm:rounded-3xl`)
- Smooth fade + scale entrance (220ms) + backdrop fade (180ms)
- Escape key + backdrop click + close button all dismiss

```blade
<x-admin.modal :show="$showForm" title="New testimonial" size="xl" onClose="cancel">
    <form wire:submit="save">...</form>
</x-admin.modal>
```

`size`: `sm` (`max-w-md`), `md` (`max-w-xl`), `lg` default (`max-w-2xl`), `xl` (`max-w-4xl`).

---

## Card pattern

The recurring card on the public site:

```blade
<article class="rounded-3xl bg-white p-6 shadow-[0_4px_20px_-10px_rgba(0,4,78,0.12)]
                 ring-1 ring-brand-100 transition hover:-translate-y-1
                 hover:shadow-[0_18px_50px_-20px_rgba(0,4,78,0.25)]
                 hover:ring-brand-200">
    ...
</article>
```

- Default shadow visible at rest (`-10px` blur, 12% opacity)
- Hover lifts (`-translate-y-1`) and intensifies the shadow + ring colour
- Brand-100 ring is visible against white; brand-200 on hover gives extra signal

---

## Buttons

| Variant       | Classes                                                                                    |
| ------------- | ------------------------------------------------------------------------------------------ |
| Primary       | `rounded-full bg-gold-500 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-900` |
| Secondary     | `rounded-full border-2 border-brand-900 px-6 py-3 text-sm font-semibold text-brand-900 hover:bg-brand-900 hover:text-white` |
| Pill (admin)  | `rounded-xl bg-gold-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-900`     |

---

## Kicker / overline label

Used on every section as a small uppercase label above the H2:

```blade
<p class="reveal-down text-xs font-semibold uppercase tracking-[0.3em] text-gold-500">
    What we stand for
</p>
```

Stays under 4 words; never punctuated.

---

## Foundation imagery

Stored at `public/images/foundation/`:

- `community-1.jpg` to `community-3.jpg` — group gatherings
- `outreach-1.jpg` to `outreach-4.webp` — programme moments
- `school-donation.jpg` — children with school supplies (used in home hero + donate hero)
- `about/team-group.jpg` — the team-photo hero on `/about`
- `about/empowering-hope.jpg` — mission/vision section photo
- `about/serving-with-heart.jpg` — legacy section photo
- `about/madam-dadaa-portrait.jpg` — circular portrait in the lavender quote band
- `leadership/*.jpg` — board headshots

Admins upload new imagery via the **Media gallery** module — those land under `storage/app/public/media/` and are served from `/storage/...`.

---

## Spacing scale

| Token  | Pixel | Where it's used                              |
| ------ | ----- | -------------------------------------------- |
| `p-3`  | 12px  | Mobile body padding (the cream frame)        |
| `p-4`  | 16px  | sm body padding                              |
| `p-6`  | 24px  | lg body padding                              |
| `p-7`  | 28px  | Card body padding                            |
| `py-20` to `py-24` | 80–96px | Section vertical rhythm        |
| `rounded-3xl` | 24px | All hero/card corners                    |
| `gap-5` / `gap-6` | 20–24px | Card grids                          |

---

## Newsletter signup

`<livewire:site.newsletter-signup source="..." />` — drop into any page footer area. The `source` prop tags the row in the `newsletter_subscribers` table so analytics can attribute signups by page.
