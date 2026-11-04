<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SponsorshipController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => 'nullable|string|max:80', 'gender' => 'nullable|in:male,female', 'country' => 'nullable|string|max:80', 'age' => 'nullable|in:0-5,6-11,12-17']);
        $filters['search'] = trim($filters['search'] ?? '');
        $query = Beneficiary::query()->publicSponsorship();
        $countries = (clone $query)->distinct()->orderBy('public_country')->pluck('public_country');
        if ($filters['search'] !== '') {
            $query->whereLike('public_display_name', '%'.$filters['search'].'%');
        }
        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }
        if (! empty($filters['country'])) {
            $query->where('public_country', $filters['country']);
        }
        if (! empty($filters['age'])) {
            [$min, $max] = array_map('intval', explode('-', $filters['age']));
            $query->whereDate('date_of_birth', '>', today()->subYears($max + 1)->toDateString())->whereDate('date_of_birth', '<=', today()->subYears($min)->toDateString());
        }

        return view('site.sponsor-a-child', ['children' => $query->withSponsoredFlag()->orderBy('is_sponsored')->orderBy('public_display_name')->paginate(9)->withQueryString(), 'countries' => $countries, 'filters' => $filters, 'faqs' => Faq::query()->onPage('sponsorship')->published()->ordered()->get()]);
    }

    public function show(string $profile): View|RedirectResponse
    {
        $child = Beneficiary::query()->publicSponsorship()
            ->where(fn ($query) => $query->where('public_sponsorship_slug', $profile)->orWhere('public_sponsorship_id', $profile))
            ->firstOrFail();

        if ($profile !== $child->public_sponsorship_slug) {
            return redirect()->route('sponsor-a-child.show', $child->public_sponsorship_slug, 301);
        }

        $child->load('sponsorshipPhotos');
        $isSponsored = $child->isSponsored();

        return view('site.sponsorship-show', compact('child', 'isSponsored'));
    }

    public function checkout(string $profile): View|RedirectResponse
    {
        $child = Beneficiary::query()->publicSponsorship()->where('public_sponsorship_slug', $profile)->firstOrFail();

        if ($child->isSponsored()) {
            return redirect()->route('sponsor-a-child.show', $child->public_sponsorship_slug);
        }

        return view('site.sponsorship-checkout', compact('child'));
    }
}
