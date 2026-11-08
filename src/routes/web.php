<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Crypto\AsymmetricController;
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

        Route::get('/hash', [HashController::class, 'show'])->name('hash');
        Route::post('/hash/digest', [HashController::class, 'digest'])
            ->middleware('throttle:60,1')->name('hash.digest');
        Route::post('/hash/hmac', [HashController::class, 'hmac'])
            ->middleware('throttle:30,1')->name('hash.hmac');
        Route::post('/hash/password', [HashController::class, 'password'])
            ->middleware('throttle:20,1')->name('hash.password');
        Route::post('/hash/password/verificar', [HashController::class, 'verifyPassword'])
            ->middleware('throttle:30,1')->name('hash.password.verify');

        Route::get('/autenticacion-cifrada', [AuthDemoController::class, 'index'])->name('auth-demo');
        Route::post('/autenticacion-cifrada', [AuthDemoController::class, 'store'])
            ->middleware('throttle:30,1')->name('auth-demo.store');
        Route::get('/autenticacion-cifrada/exportar', [AuthDemoController::class, 'export'])->name('auth-demo.export');
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
    });

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');
    });

require __DIR__.'/auth.php';
