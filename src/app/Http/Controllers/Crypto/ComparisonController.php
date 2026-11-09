<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Services\Crypto\CryptoOverheadService;
use Illuminate\View\View;

class ComparisonController extends Controller
{
    public function __construct(private readonly CryptoOverheadService $overhead) {}

    public function show(): View
    {
        return view('crypto.comparison', [
            'rows' => $this->overhead->rows(),
        ]);
    }
}
