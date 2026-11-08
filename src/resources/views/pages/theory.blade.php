<x-public-layout>
    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Teoría</p>
        <h1 class="mt-2 text-3xl font-bold">Simétrico, asimétrico y hash</h1>
        <p class="mt-4 max-w-3xl text-gray-600 dark:text-gray-400">
            Tres familias con objetivos distintos. Confundirlas es uno de los errores más comunes al diseñar autenticación.
        </p>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Cifrado simétrico</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Una sola llave cifra y descifra. Es rápido y adecuado para grandes volúmenes. Requiere compartir la llave por un canal seguro.
                </p>
                <p class="mt-3 text-xs text-gray-500">Ejemplos: AES-GCM, ChaCha20-Poly1305. En CriptoLab se usa para el usuario en la pantalla 5.4.</p>
            </article>

            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Cifrado asimétrico</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Una llave pública cifra o verifica y la llave privada descifra o firma. Resuelve el intercambio de llaves, pero es lento y tiene límite de tamaño.
                </p>
                <p class="mt-3 text-xs text-gray-500">Ejemplos: RSA-OAEP, X25519 sealed box, híbrido RSA+AES. En CriptoLab se usa para la contraseña de la pantalla 5.4.</p>
            </article>

            <article class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
                <h2 class="text-lg font-semibold">Hash y derivación</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Un hash es irreversible y no usa llave (salvo HMAC). Sirve para integridad y contraseñas; nunca se “descifra”. La derivación convierte una frase en una llave.
                </p>
                <p class="mt-3 text-xs text-gray-500">Ejemplos: SHA-256, Argon2id, PBKDF2. El login real de CriptoLab usa Argon2id.</p>
            </article>
        </div>

        <section class="mt-10 overflow-x-auto rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
            <h2 class="text-lg font-semibold">Comparación rápida</h2>
            <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="py-2 pr-4">Técnica</th>
                        <th class="py-2 pr-4">Reversible</th>
                        <th class="py-2 pr-4">Llave</th>
                        <th class="py-2 pr-4">Velocidad</th>
                        <th class="py-2">Uso típico</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr><td class="py-3 pr-4 font-semibold">Simétrico</td><td class="py-3 pr-4">Sí</td><td class="py-3 pr-4">Una llave secreta</td><td class="py-3 pr-4">Muy rápido</td><td class="py-3">Datos masivos y sesiones</td></tr>
                    <tr><td class="py-3 pr-4 font-semibold">Asimétrico</td><td class="py-3 pr-4">Sí</td><td class="py-3 pr-4">Par pública/privada</td><td class="py-3 pr-4">Lento</td><td class="py-3">Intercambio de llaves y firmas</td></tr>
                    <tr><td class="py-3 pr-4 font-semibold">Hash</td><td class="py-3 pr-4">No</td><td class="py-3 pr-4">No (HMAC sí)</td><td class="py-3 pr-4">Rápido</td><td class="py-3">Integridad y contraseñas</td></tr>
                    <tr><td class="py-3 pr-4 font-semibold">Base64</td><td class="py-3 pr-4">Sí, sin llave</td><td class="py-3 pr-4">No</td><td class="py-3 pr-4">Trivial</td><td class="py-3">Codificar para transporte</td></tr>
                </tbody>
            </table>
        </section>

        <section class="mt-10 rounded-xl bg-white p-6 shadow-sm dark:bg-gray-900">
            <h2 class="text-lg font-semibold">Equivalencias Java ↔ PHP</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                La actividad original se planteó en Java. Esta entrega usa PHP, pero cubre las mismas APIs conceptuales:
            </p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="py-2 pr-4">Java</th>
                            <th class="py-2">PHP / Laravel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr><td class="py-2 pr-4"><code>Cipher</code></td><td class="py-2"><code>openssl_encrypt/decrypt</code>, <code>sodium_crypto_*</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>KeyGenerator</code> / <code>SecretKey</code></td><td class="py-2"><code>random_bytes()</code>, <code>sodium_crypto_secretbox_keygen()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>SecretKeySpec</code> / <code>IvParameterSpec</code></td><td class="py-2">Llave + IV en <code>openssl_encrypt</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>KeyPairGenerator</code> / <code>KeyPair</code></td><td class="py-2"><code>openssl_pkey_new()</code>, <code>sodium_crypto_box_keypair()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>PublicKey</code> / <code>PrivateKey</code></td><td class="py-2"><code>openssl_pkey_get_public/private()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>Signature</code></td><td class="py-2"><code>openssl_sign()</code> / <code>openssl_verify()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>MessageDigest</code></td><td class="py-2"><code>hash()</code> / <code>hash_algos()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>Mac</code></td><td class="py-2"><code>hash_hmac()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>SecretKeyFactory</code> (PBKDF2)</td><td class="py-2"><code>hash_pbkdf2()</code>, <code>sodium_crypto_pwhash()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>SecureRandom</code></td><td class="py-2"><code>random_bytes()</code> / <code>random_int()</code></td></tr>
                        <tr><td class="py-2 pr-4"><code>Base64</code></td><td class="py-2"><code>base64_encode/decode</code></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-10 rounded-xl border border-amber-300 bg-amber-50 p-6 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <h2 class="font-semibold">Reglas de oro</h2>
            <ul class="mt-3 list-inside list-disc space-y-1">
                <li>Nunca reutilizar IV/nonce: se genera con <code>random_bytes()</code> en cada operación.</li>
                <li>No inventar algoritmos ni modos propios; usar OpenSSL y Sodium.</li>
                <li>Contraseñas con Argon2id o bcrypt, nunca con cifrado reversible.</li>
                <li>Comparar valores sensibles con <code>hash_equals()</code> y usar verificación de firmas de la librería.</li>
                <li>No mostrar contraseñas en claro fuera de una demostración educativa.</li>
            </ul>
        </section>
    </section>
</x-public-layout>
