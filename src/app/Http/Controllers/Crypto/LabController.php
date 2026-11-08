<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Models\CryptoOperation;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabController extends Controller
{
    public function index(): View
    {
        return view('crypto.lab', [
            'symmetric' => app(SymmetricCipherRegistry::class)->catalog(),
            'asymmetric' => app(AsymmetricCipherRegistry::class)->catalog(),
            'signers' => app(SignerRegistry::class)->catalog(),
        ]);
    }

    public function history(Request $request): View
    {
        $operations = CryptoOperation::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('algorithm'), fn ($query) => $query->where('algorithm', $request->string('algorithm')->toString()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('crypto.history', [
            'operations' => $operations,
            'types' => CryptoOperation::query()
                ->where('user_id', $request->user()->id)
                ->distinct()
                ->orderBy('type')
                ->pluck('type'),
            'algorithms' => CryptoOperation::query()
                ->where('user_id', $request->user()->id)
                ->distinct()
                ->orderBy('algorithm')
                ->pluck('algorithm'),
        ]);
    }
}
