<x-public-layout>
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-violet-700 to-slate-900 opacity-95"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-24 text-white sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-200">Actividad evaluativa · Eje 3</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-extrabold leading-tight sm:text-5xl">
                Criptografía aplicada en PHP y Laravel
            </h1>
            <p class="mt-6 max-w-2xl text-lg text-indigo-100">
                Un laboratorio web completo para cifrar, descifrar, firmar y comparar algoritmos reales con OpenSSL y Sodium. Incluye autenticación, cifrado en reposo y una demostración reversible con usuario y contraseña.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('crypto.lab') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-bold text-indigo-700 hover:bg-indigo-50">Abrir laboratorio</a>
                    <a href="{{ route('crypto.auth-demo') }}" class="rounded-lg border border-white/40 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">Formulario cifrado</a>
                @else
                    <a href="{{ route('register') }}" class="rounded-lg bg-white px-5 py-3 text-sm font-bold text-indigo-700 hover:bg-indigo-50">Crear cuenta</a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-white/40 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">Iniciar sesión</a>
                @endauth
                <a href="{{ route('theory') }}" class="rounded-lg border border-white/40 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">Ver teoría</a>
            </div>

            <p class="mt-8 max-w-2xl text-sm text-indigo-200">
                El enunciado original plantea Java; esta entrega implementa los mismos conceptos con PHP/Laravel. El README incluye la tabla de equivalencias Java ↔ PHP para justificar la adaptación.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Simétrico</p>
                <h2 class="mt-2 text-lg font-semibold">Una llave, ida y vuelta</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    AES-256-GCM, AES-256-CBC, AES-128-CTR, ChaCha20-Poly1305 y XSalsa20. IV/nonce aleatorio y demostración de que dos cifrados difieren.
                </p>
            </article>

            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Asimétrico</p>
                <h2 class="mt-2 text-lg font-semibold">Pública y privada</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    RSA-OAEP 2048/4096, sealed box X25519 y cifrado híbrido RSA+AES. Firmas RSA-SHA256 y Ed25519.
                </p>
            </article>

            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wide text-sky-600 dark:text-sky-400">Hash</p>
                <h2 class="mt-2 text-lg font-semibold">Integridad y contraseñas</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    SHA-256, SHA-512, SHA3-256, BLAKE2b, HMAC-SHA256, bcrypt y Argon2id. Hash no es cifrado.
                </p>
            </article>

            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <p class="text-xs font-bold uppercase tracking-wide text-violet-600 dark:text-violet-400">En reposo</p>
                <h2 class="mt-2 text-lg font-semibold">Datos protegidos</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Email y teléfono cifrados, hash ciego para búsquedas, llaves privadas protegidas con llave maestra y sesiones cifradas.
                </p>
            </article>
        </div>
    </section>

    <section class="border-y border-gray-200 bg-white py-16 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <h2 class="text-2xl font-bold">Formulario de autenticación cifrado</h2>
                <p class="mt-3 text-gray-600 dark:text-gray-400">
                    La pantalla de autenticación cifrada replica el ejercicio del enunciado: el usuario se cifra con un algoritmo simétrico y la contraseña con RSA-OAEP. La tabla muestra texto claro, texto cifrado, algoritmos, fecha y un botón para descifrar.
                </p>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Esta demostración es independiente del login real. El login siempre usa hash Argon2id.
                </p>
                <div class="mt-5 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-700 dark:bg-red-950 dark:text-red-100">
                    Mostrar contraseñas en claro solo es aceptable en esta pantalla educativa. Nunca debe hacerse en un sistema real.
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-950">
                <h3 class="font-semibold">Algoritmos incluidos</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="font-medium">Simétricos</dt><dd class="text-right text-gray-600 dark:text-gray-400">AES-256-GCM · AES-256-CBC · AES-128-CTR · ChaCha20 · XSalsa20</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">Asimétricos</dt><dd class="text-right text-gray-600 dark:text-gray-400">RSA-OAEP 2048/4096 · Sealed box · Híbrido</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">Firmas</dt><dd class="text-right text-gray-600 dark:text-gray-400">RSA-SHA256 · Ed25519</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">Hashes</dt><dd class="text-right text-gray-600 dark:text-gray-400">SHA-2 · SHA3 · BLAKE2b · HMAC</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">Contraseñas</dt><dd class="text-right text-gray-600 dark:text-gray-400">Argon2id · bcrypt</dd></div>
                </dl>
            </div>
        </div>
    </section>
</x-public-layout>
