<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crypto;

use App\Exceptions\CryptoException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Crypto\Concerns\HandlesCryptoResponses;
use App\Http\Requests\Crypto\AuthDemoStoreRequest;
use App\Models\CryptoDemoEntry;
use App\Services\Audit\AuditService;
use App\Services\Crypto\AuthDemoService;
use App\Services\Crypto\CryptoOperationService;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthDemoController extends Controller
{
    use HandlesCryptoResponses;

    public function __construct(
        private readonly AuthDemoService $demo,
        private readonly SymmetricCipherRegistry $symmetric,
        private readonly AsymmetricCipherRegistry $asymmetric,
        private readonly CryptoOperationService $operations,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        return view('crypto.auth-demo', [
            'entries' => $request->user()->cryptoDemoEntries()->latest('id')->paginate(15),
            'symmetricCatalog' => $this->symmetric->catalog(),
            'asymmetricCatalog' => $this->asymmetric->catalog(),
        ]);
    }

    public function store(AuthDemoStoreRequest $request): RedirectResponse
    {
        try {
            $entry = $this->demo->create($request->user(), [
                'username' => $request->string('username')->toString(),
                'password' => $request->string('password')->toString(),
                'sym_algorithm' => $request->string('sym_algorithm')->toString(),
                'asym_algorithm' => $request->string('asym_algorithm')->toString(),
                'swap' => $request->boolean('swap'),
            ]);

            $algorithm = $entry->sym_algorithm.'+'.$entry->asym_algorithm;

            $this->operations->log('demo_entry', $algorithm, $request->user(), 'auth-demo');
            $this->audit->log($request->user(), 'demo_entry_created', 'Registro de demostración creado', [
                'entry_id' => $entry->id,
                'swap' => (bool) ($entry->meta['swap'] ?? false),
                'sym_algorithm' => $entry->sym_algorithm,
                'asym_algorithm' => $entry->asym_algorithm,
            ]);

            return redirect()->route('crypto.auth-demo')
                ->with('status', 'Registro cifrado creado. La tabla muestra el texto en claro y el texto cifrado.');
        } catch (CryptoException $exception) {
            return back()->withErrors(['demo' => $exception->getMessage()])->withInput();
        }
    }

    public function decrypt(Request $request, int $entry): JsonResponse
    {
        try {
            $model = $this->ownedEntry($request, $entry);
            $plain = $this->demo->decrypt($model);

            $this->operations->log(
                'decrypt',
                $model->sym_algorithm.'+'.$model->asym_algorithm,
                $request->user(),
                'auth-demo',
            );
            $this->audit->log($request->user(), 'demo_entry_decrypted', 'Registro de demostración descifrado', [
                'entry_id' => $model->id,
            ]);

            return $this->cryptoSuccess([
                'entry_id' => $model->id,
                'username' => $plain['username'],
                'password' => $plain['password'],
            ]);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function destroy(Request $request, int $entry): RedirectResponse
    {
        $model = $this->ownedEntry($request, $entry);

        $this->audit->log($request->user(), 'demo_entry_deleted', 'Registro de demostración eliminado', [
            'entry_id' => $model->id,
        ]);
        $this->operations->log(
            'delete_demo_entry',
            $model->sym_algorithm.'+'.$model->asym_algorithm,
            $request->user(),
            'auth-demo',
        );

        $model->delete();

        return redirect()->route('crypto.auth-demo')->with('status', 'Registro eliminado.');
    }

    public function export(Request $request)
    {
        $entries = $this->demo->export($request->user());

        $this->operations->log('export_demo_entries', 'json', $request->user(), 'auth-demo');
        $this->audit->log($request->user(), 'demo_entries_exported', 'Registros de demostración exportados');

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'notice' => 'Exportación educativa con textos claros. No usar contraseñas reales.',
            'entries' => $entries,
        ], 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="criptolab-demostracion.json"',
        ]);
    }

    private function ownedEntry(Request $request, int $id): CryptoDemoEntry
    {
        $entry = CryptoDemoEntry::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if ($entry === null) {
            abort(404);
        }

        return $entry;
    }
}
