<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Crypto\AesModesController;
use App\Http\Controllers\Crypto\AsymmetricController;
use App\Http\Controllers\Crypto\BenchmarkController;
use App\Http\Controllers\Crypto\CertificateController;
use App\Http\Controllers\Crypto\ComparisonController;
use App\Http\Controllers\Crypto\FileCryptoController;
use App\Http\Controllers\Crypto\GcmAadController;
use App\Http\Controllers\Crypto\KeyImportController;
use App\Http\Controllers\Crypto\QuizController;
use App\Http\Controllers\Crypto\RainbowTableController;
use App\Http\Controllers\Crypto\SignEncryptController;
use App\Http\Controllers\Crypto\TimingComparisonController;
use App\Http\Controllers\Crypto\X25519Controller;
use App\Http\Controllers\Crypto\AuthDemoController;
use App\Http\Controllers\Crypto\HashController;
use App\Http\Controllers\Crypto\KeypairController;
use App\Http\Controllers\Crypto\LabController;
use App\Http\Controllers\Crypto\SymmetricController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/teoria', [PageController::class, 'theory'])->name('theory');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])
    ->prefix('lab')
    ->name('crypto.')
    ->group(function () {
        Route::get('/', [LabController::class, 'index'])->name('lab');

        Route::get('/simetrico', [SymmetricController::class, 'show'])->name('symmetric');
        Route::post('/simetrico/llave', [SymmetricController::class, 'generateKey'])
            ->middleware('throttle:30,1')->name('symmetric.key');
        Route::post('/simetrico/derivar', [SymmetricController::class, 'derive'])
            ->middleware('throttle:20,1')->name('symmetric.derive');
        Route::post('/simetrico/cifrar', [SymmetricController::class, 'encrypt'])
            ->middleware('throttle:60,1')->name('symmetric.encrypt');
        Route::post('/simetrico/descifrar', [SymmetricController::class, 'decrypt'])
            ->middleware('throttle:60,1')->name('symmetric.decrypt');
        Route::post('/simetrico/demostrar-iv', [SymmetricController::class, 'demonstrateIv'])
            ->middleware('throttle:20,1')->name('symmetric.iv');

        Route::get('/simetrico/archivos', [FileCryptoController::class, 'show'])->name('symmetric.files');
        Route::post('/simetrico/archivos/cifrar', [FileCryptoController::class, 'encrypt'])
            ->middleware('throttle:10,1')->name('symmetric.files.encrypt');
        Route::post('/simetrico/archivos/descifrar', [FileCryptoController::class, 'decrypt'])
            ->middleware('throttle:10,1')->name('symmetric.files.decrypt');

        Route::get('/simetrico/modos', [AesModesController::class, 'show'])->name('symmetric.modes');
        Route::post('/simetrico/modos/comparar', [AesModesController::class, 'compare'])
            ->middleware('throttle:20,1')->name('symmetric.modes.compare');
        Route::post('/simetrico/modos/nonce-reutilizado', [AesModesController::class, 'nonceReuse'])
            ->middleware('throttle:20,1')->name('symmetric.modes.nonce');

        Route::get('/simetrico/aad', [GcmAadController::class, 'show'])->name('symmetric.aad');
        Route::post('/simetrico/aad/cifrar', [GcmAadController::class, 'encrypt'])
            ->middleware('throttle:30,1')->name('symmetric.aad.encrypt');
        Route::post('/simetrico/aad/descifrar', [GcmAadController::class, 'decrypt'])
            ->middleware('throttle:30,1')->name('symmetric.aad.decrypt');

        Route::get('/asimetrico', [AsymmetricController::class, 'show'])->name('asymmetric');
        Route::post('/asimetrico/llaves', [AsymmetricController::class, 'generateKeypair'])
            ->middleware('throttle:10,1')->name('asymmetric.keypair');
        Route::post('/asimetrico/firmas/llaves', [AsymmetricController::class, 'generateSignerKeypair'])
            ->middleware('throttle:10,1')->name('asymmetric.signer.keypair');
        Route::post('/asimetrico/cifrar', [AsymmetricController::class, 'encrypt'])
            ->middleware('throttle:30,1')->name('asymmetric.encrypt');
        Route::post('/asimetrico/descifrar', [AsymmetricController::class, 'decrypt'])
            ->middleware('throttle:30,1')->name('asymmetric.decrypt');
        Route::post('/asimetrico/firmar', [AsymmetricController::class, 'sign'])
            ->middleware('throttle:30,1')->name('asymmetric.sign');
        Route::post('/asimetrico/verificar', [AsymmetricController::class, 'verify'])
            ->middleware('throttle:30,1')->name('asymmetric.verify');

        Route::get('/asimetrico/x25519', [X25519Controller::class, 'show'])->name('asymmetric.x25519');
        Route::post('/asimetrico/x25519/generar', [X25519Controller::class, 'generate'])
            ->middleware('throttle:30,1')->name('asymmetric.x25519.generate');
        Route::post('/asimetrico/x25519/secreto', [X25519Controller::class, 'shared'])
            ->middleware('throttle:30,1')->name('asymmetric.x25519.shared');

        Route::get('/asimetrico/firma-cifrado', [SignEncryptController::class, 'show'])->name('asymmetric.sign-encrypt');
        Route::post('/asimetrico/firma-cifrado/llaves', [SignEncryptController::class, 'generatePairs'])
            ->middleware('throttle:10,1')->name('asymmetric.sign-encrypt.keys');
        Route::post('/asimetrico/firma-cifrado/cifrar', [SignEncryptController::class, 'encrypt'])
            ->middleware('throttle:20,1')->name('asymmetric.sign-encrypt.encrypt');
        Route::post('/asimetrico/firma-cifrado/descifrar', [SignEncryptController::class, 'decrypt'])
            ->middleware('throttle:20,1')->name('asymmetric.sign-encrypt.decrypt');

        Route::get('/asimetrico/certificados', [CertificateController::class, 'show'])->name('asymmetric.certificates');
        Route::post('/asimetrico/certificados/generar', [CertificateController::class, 'generate'])
            ->middleware('throttle:5,1')->name('asymmetric.certificates.generate');
        Route::post('/asimetrico/certificados/inspeccionar', [CertificateController::class, 'inspect'])
            ->middleware('throttle:20,1')->name('asymmetric.certificates.inspect');

        Route::get('/asimetrico/importar', [KeyImportController::class, 'show'])->name('asymmetric.import');
        Route::post('/asimetrico/importar/inspeccionar', [KeyImportController::class, 'inspect'])
            ->middleware('throttle:20,1')->name('asymmetric.import.inspect');

        Route::get('/hash', [HashController::class, 'show'])->name('hash');
        Route::post('/hash/digest', [HashController::class, 'digest'])
            ->middleware('throttle:60,1')->name('hash.digest');
        Route::post('/hash/hmac', [HashController::class, 'hmac'])
            ->middleware('throttle:30,1')->name('hash.hmac');
        Route::post('/hash/password', [HashController::class, 'password'])
            ->middleware('throttle:20,1')->name('hash.password');
        Route::post('/hash/password/verificar', [HashController::class, 'verifyPassword'])
            ->middleware('throttle:30,1')->name('hash.password.verify');

        Route::get('/hash/tiempo-seguro', [TimingComparisonController::class, 'show'])->name('hash.timing');
        Route::post('/hash/tiempo-seguro/comparar', [TimingComparisonController::class, 'compare'])
            ->middleware('throttle:10,1')->name('hash.timing.compare');

        Route::get('/hash/rainbow', [RainbowTableController::class, 'show'])->name('hash.rainbow');
        Route::post('/hash/rainbow/consultar', [RainbowTableController::class, 'consult'])
            ->middleware('throttle:20,1')->name('hash.rainbow.consult');

        Route::get('/autenticacion-cifrada', [AuthDemoController::class, 'index'])->name('auth-demo');
        Route::post('/autenticacion-cifrada', [AuthDemoController::class, 'store'])
            ->middleware('throttle:30,1')->name('auth-demo.store');
        Route::get('/autenticacion-cifrada/exportar', [AuthDemoController::class, 'export'])->name('auth-demo.export');
        Route::get('/autenticacion-cifrada/exportar.csv', [AuthDemoController::class, 'exportCsv'])->name('auth-demo.export-csv');
        Route::post('/autenticacion-cifrada/{entry}/recifrar', [AuthDemoController::class, 'reencrypt'])
            ->whereNumber('entry')->middleware('throttle:20,1')->name('auth-demo.reencrypt');
        Route::post('/autenticacion-cifrada/{entry}/alterar', [AuthDemoController::class, 'tamper'])
            ->whereNumber('entry')->middleware('throttle:20,1')->name('auth-demo.tamper');
        Route::get('/autenticacion-cifrada/{entry}/crudo', [AuthDemoController::class, 'raw'])
            ->whereNumber('entry')->name('auth-demo.raw');
        Route::post('/autenticacion-cifrada/{entry}/descifrar', [AuthDemoController::class, 'decrypt'])
            ->whereNumber('entry')->middleware('throttle:30,1')->name('auth-demo.decrypt');
        Route::delete('/autenticacion-cifrada/{entry}', [AuthDemoController::class, 'destroy'])
            ->whereNumber('entry')->name('auth-demo.destroy');

        Route::get('/historial', [LabController::class, 'history'])->name('history');
        Route::get('/llaves', [KeypairController::class, 'index'])->name('keypairs.index');
        Route::get('/llaves/{id}/publica', [KeypairController::class, 'downloadPublic'])
            ->whereNumber('id')->name('keypairs.public');
        Route::delete('/llaves/{id}', [KeypairController::class, 'destroy'])
            ->whereNumber('id')->name('keypairs.destroy');

        Route::get('/benchmark', [BenchmarkController::class, 'show'])->name('benchmark');
        Route::post('/benchmark/ejecutar', [BenchmarkController::class, 'run'])
            ->middleware('throttle:3,1')->name('benchmark.run');

        Route::get('/comparador', [ComparisonController::class, 'show'])->name('comparison');

        Route::get('/retos', [QuizController::class, 'show'])->name('quiz');
        Route::post('/retos/evaluar', [QuizController::class, 'evaluate'])
            ->middleware('throttle:30,1')->name('quiz.evaluate');
    });

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');
    });

require __DIR__.'/auth.php';
