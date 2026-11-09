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
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function reencrypt(Request $request, int $entry): RedirectResponse
    {
        try {
            $model = $this->ownedEntry($request, $entry);
            $new = $this->demo->reencrypt($model);

            $this->audit->log($request->user(), 'demo_entry_reencrypted', 'Registro de demostración re-cifrado con nuevo IV', [
                'source_entry_id' => $model->id,
                'entry_id' => $new->id,
            ]);
            $this->operations->log('reencrypt', $new->sym_algorithm.'+'.$new->asym_algorithm, $request->user(), 'auth-demo');

            return redirect()->route('crypto.auth-demo')
                ->with('status', 'Registro re-cifrado con un IV y una llave nuevos (nueva fila creada).');
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (CryptoException $exception) {
            return back()->withErrors(['demo' => $exception->getMessage()]);
        }
    }

    public function tamper(Request $request, int $entry): JsonResponse
    {
        try {
            $model = $this->ownedEntry($request, $entry);
            $envelope = CryptoEnvelope::fromJson($model->password_encrypted);
            $symmetric = $this->symmetric->all()[$envelope->algorithm] ?? null;

            if ($symmetric !== null && ($symmetric->metadata()['authenticated'] ?? false) !== true) {
                throw new CryptoException('Ese algoritmo simétrico no autentica; la manipulación no se puede detectar. Cambia a GCM o ChaCha20.');
            }

            $raw = base64_decode($envelope->ciphertext, true);

            if ($raw === false || $raw === '') {
                throw new \RuntimeException('El ciphertext no se pudo preparar para la demostración.');
            }

            $raw[0] = chr(ord($raw[0]) ^ 0x01);
            $clone = $model->replicate();
            $clone->password_encrypted = (new CryptoEnvelope(
                algorithm: $envelope->algorithm,
                ciphertext: base64_encode($raw),
                iv: $envelope->iv,
                tag: $envelope->tag,
                meta: $envelope->meta,
            ))->toJson();

            $this->demo->decrypt($clone);

            return $this->cryptoSuccess([
                'message' => 'El sistema aceptó el ciphertext manipulado. Esto no debería ocurrir.',
            ]);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return $this->cryptoFailure($exception);
        }
    }

    public function raw(Request $request, int $entry): JsonResponse
    {
        $model = $this->ownedEntry($request, $entry);
        $raw = DB::table('crypto_demo_entries')->where('id', $model->id)->first();

        $this->operations->log('raw_view', 'database', $request->user(), 'auth-demo');

        return $this->cryptoSuccess([
            'row' => [
                'id' => $raw->id,
                'username_plain_ciphertext' => $raw->username_plain,
                'username_encrypted' => $raw->username_encrypted,
                'password_plain_ciphertext' => $raw->password_plain,
                'password_encrypted' => $raw->password_encrypted,
                'sym_algorithm' => $raw->sym_algorithm,
                'asym_algorithm' => $raw->asym_algorithm,
                'meta' => $raw->meta,
            ],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $entries = $this->demo->export($request->user());
        $this->operations->log('export_demo_entries', 'csv', $request->user(), 'auth-demo');
        $this->audit->log($request->user(), 'demo_entries_exported', 'Registros de demostración exportados a CSV');

        return response()->streamDownload(function () use ($entries): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['id', 'usuario_claro', 'usuario_cifrado', 'password_claro', 'password_cifrado', 'sim_algorithm', 'asym_algorithm', 'swap', 'created_at']);

            foreach ($entries as $entry) {
                fputcsv($output, [
                    $entry['id'],
                    $this->csvSafe($entry['username_plain']),
                    $entry['username_encrypted'],
                    $this->csvSafe($entry['password_plain']),
                    $entry['password_encrypted'],
                    $entry['sym_algorithm'],
                    $entry['asym_algorithm'],
                    $entry['swap'] ? '1' : '0',
                    $entry['created_at'],
                ]);
            }

            fclose($output);
        }, 'criptolab-demostracion.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
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
