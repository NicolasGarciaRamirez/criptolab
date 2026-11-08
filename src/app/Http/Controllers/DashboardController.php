<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'operationsCount' => $user->cryptoOperations()->count(),
            'demoEntriesCount' => $user->cryptoDemoEntries()->count(),
            'keypairsCount' => $user->cryptoKeypairs()->count(),
            'recentOperations' => $user->cryptoOperations()->latest('id')->limit(8)->get(),
        ]);
    }
}
