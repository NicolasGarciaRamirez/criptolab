<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.landing');
    }

    public function theory(): View
    {
        return view('pages.theory');
    }
}
