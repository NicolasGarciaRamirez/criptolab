<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Panel de {{ Auth::user()->name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Criptografía aplicada con Laravel, PostgreSQL y Sodium/OpenSSL.</p>
            </div>
            <a href="{{ route('crypto.lab') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Abrir laboratorio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Operaciones</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $operationsCount }}</p>
                    <a href="{{ route('crypto.history') }}" class="mt-2 inline-block text-xs underline text-indigo-600 dark:text-indigo-400">Ver historial</a>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Entradas de demostración</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $demoEntriesCount }}</p>
                    <a href="{{ Route::has('crypto.auth-demo') ? route('crypto.auth-demo') : '#' }}" class="mt-2 inline-block text-xs underline text-indigo-600 dark:text-indigo-400">Formulario cifrado</a>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Pares de llaves</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $keypairsCount }}</p>
                    <a href="{{ route('crypto.keypairs.index') }}" class="mt-2 inline-block text-xs underline text-indigo-600 dark:text-indigo-400">Mis llaves</a>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('crypto.symmetric') }}" class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Simétrico</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">AES-GCM, AES-CBC, AES-CTR, ChaCha20 y XSalsa20.</p>
                </a>
                <a href="{{ route('crypto.asymmetric') }}" class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Asimétrico</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">RSA-OAEP, sealed box, híbrido y firmas.</p>
                </a>
                <a href="{{ route('crypto.hash') }}" class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Hash</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">SHA, HMAC, bcrypt y Argon2id.</p>
                </a>
                <a href="{{ Route::has('crypto.auth-demo') ? route('crypto.auth-demo') : '#' }}" class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Autenticación cifrada</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Usuario simétrico + contraseña asimétrica.</p>
                </a>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Últimas operaciones</h3>
                    <a href="{{ route('crypto.history') }}" class="text-xs underline text-indigo-600 dark:text-indigo-400">Ver todo</a>
                </div>

                @if ($recentOperations->isEmpty())
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Todavía no has realizado operaciones.</p>
                @else
                    <ul class="mt-3 divide-y divide-gray-100 text-sm dark:divide-gray-700">
                        @foreach ($recentOperations as $operation)
                            <li class="flex items-center justify-between py-2">
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $operation->type }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">· {{ $operation->algorithm }}</span>
                                </div>
                                <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                    <span class="{{ $operation->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ $operation->success ? 'OK' : 'Falló' }}</span>
                                    · {{ $operation->created_at?->format('Y-m-d H:i') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                <strong>Recuerda:</strong> las contraseñas reales del login usan hash Argon2id. La pantalla de autenticación cifrada es solo una demostración educativa.
            </div>
        </div>
    </div>
</x-app-layout>
