<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('site.legal.privacy');
    }

    public function terms(): View
    {
        return view('site.legal.terms');
    }

    public function cookies(): View
    {
        return view('site.legal.cookies');
    }

    public function disclaimer(): View
    {
        return view('site.legal.disclaimer');
    }
}
