<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\KeypairService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class KeypairController extends Controller
{
    public function __construct(
        private readonly KeypairService $keypairs,
        private readonly CryptoOperationService $operations,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        return view('crypto.keypairs.index', [
            'keypairs' => $request->user()->cryptoKeypairs()->latest()->paginate(10),
        ]);
    }

    public function downloadPublic(Request $request, int $id): Response
    {
        $keypair = $request->user()->cryptoKeypairs()->findOrFail($id);

        $filename = 'llave-publica-'.$keypair->id.'.txt';

        $this->operations->log('export_public_key', $keypair->algorithm, $request->user(), 'keypairs');

        return response($keypair->public_key, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $keypair = $request->user()->cryptoKeypairs()->findOrFail($id);

        $this->audit->log($request->user(), 'keypair_deleted', 'Par de llaves eliminado', [
            'algorithm' => $keypair->algorithm,
            'keypair_id' => $keypair->id,
        ]);
        $this->operations->log('delete_keypair', $keypair->algorithm, $request->user(), 'keypairs');

        $this->keypairs->delete($keypair);

        return redirect()->route('crypto.keypairs.index')
            ->with('status', 'Llave eliminada. Las entradas de demostración que la usaban ya no podrán descifrarse.');
    }
}
