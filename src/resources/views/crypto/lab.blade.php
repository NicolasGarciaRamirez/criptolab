<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Laboratorio criptográfico
            </h2>
            <a href="{{ route('crypto.history') }}" class="text-sm underline text-gray-600 dark:text-gray-400">
                Historial
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                <strong>Entorno educativo.</strong> Las operaciones se registran sin guardar textos claros ni llaves privadas. No uses contraseñas reales en el laboratorio.
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('crypto.symmetric') }}" class="block rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Simétrico</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">AES y ChaCha20</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Una misma llave cifra y descifra. IV/nonce aleatorio, GCM autenticado, CBC y alternativas modernas.
                    </p>
                    <p class="mt-4 text-xs text-gray-500">{{ count($symmetric) }} algoritmos disponibles</p>
                </a>

                <a href="{{ route('crypto.asymmetric') }}" class="block rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Asimétrico</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">RSA, sealed box e híbrido</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Llave pública para cifrar, llave privada para descifrar. Firmas RSA-SHA256 y Ed25519.
                    </p>
                    <p class="mt-4 text-xs text-gray-500">{{ count($asymmetric) }} cifrados · {{ count($signers) }} firmas</p>
                </a>

                <a href="{{ route('crypto.hash') }}" class="block rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-400">Hash</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Integridad y contraseñas</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        SHA-256, SHA-512, SHA3, BLAKE2b, HMAC, bcrypt y Argon2id. Compara hash vs cifrado vs Base64.
                    </p>
                    <p class="mt-4 text-xs text-gray-500">No es cifrado: es irreversible</p>
                </a>

                <a href="{{ route('crypto.keypairs.index') }}" class="block rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-400">Llaves</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Mis pares guardados</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Consulta llaves públicas y descárgalas. Las llaves privadas se guardan cifradas y no se vuelven a mostrar.
                    </p>
                    <p class="mt-4 text-xs text-gray-500">Llave maestra + cast cifrado</p>
                </a>
            </div>

            @if (Route::has('crypto.auth-demo'))
                <a href="{{ route('crypto.auth-demo') }}" class="block rounded-lg border-2 border-indigo-500 bg-white p-6 shadow-sm transition hover:shadow-md dark:bg-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Requisito de la actividad</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Formulario de autenticación cifrado</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Cifra el usuario con criptografía simétrica y la contraseña con criptografía asimétrica. Muestra el texto en claro y el cifrado, y permite descifrar cada registro.
                    </p>
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
