<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Hash, HMAC y contraseñas
            </h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="hashLab({
            algorithms: @js($algorithms),
            routes: @js([
                'digest' => route('crypto.hash.digest'),
                'hmac' => route('crypto.hash.hmac'),
                'password' => route('crypto.hash.password'),
                'passwordVerify' => route('crypto.hash.password.verify'),
            ])
        })"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="El hash produce una huella de tamaño fijo; el salt y el costo hacen único cada hash de contraseña y difícil de precomputar."
                title="Hash, HMAC y contraseñas"
                objective="Diferenciar hash, HMAC, cifrado y Base64; comprobar que las contraseñas usan salt y que un hash no se descifra."
                :steps="[
                    'Calcula un digest con SHA-256 y repite con el mismo texto: debe ser idéntico.',
                    'Cambia un carácter y observa el efecto avalancha en el hash.',
                    'Calcula un HMAC con una llave y verifica el resultado esperado.',
                    'Genera un hash bcrypt o Argon2id y verifica la contraseña correcta e incorrecta.',
                    'Revisa la tabla comparativa: hash no es cifrado y Base64 no es ninguna de las dos cosas.',
                ]"
                :expected="[
                    'El mismo texto y algoritmo producen el mismo digest.',
                    'La contraseña correcta valida y la incorrecta falla.',
                    'Dos hashes Argon2id de la misma contraseña son distintos por el salt aleatorio.',
                    'HMAC cambia si cambia el mensaje o la llave.',
                ]"
                teacher="Evalúa que el estudiante explique por qué no se usa MD5/SHA-1 para contraseñas y por qué un salt evita tablas precomputadas."
                warning="El demo de rainbow table usa un diccionario local y algoritmos obsoletos solo con fines educativos."
            />

            <div class="grid gap-3 sm:grid-cols-2">
                <a href="{{ route('crypto.hash.timing') }}" class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Comparación en tiempo constante</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">=== frente a hash_equals() y ataques de temporización.</p>
                </a>
                <a href="{{ route('crypto.hash.rainbow') }}" class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Rainbow table</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">MD5/SHA-1 frente a Argon2id con salt.</p>
                </a>
            </div>

            <div class="rounded-lg border border-sky-300 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-700 dark:bg-sky-950 dark:text-sky-200">
                Un hash es una función irreversible: no se descifra. Sirve para integridad y para guardar contraseñas. Base64 no es cifrado ni hash: es solo una codificación reversible.
            </div>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Digest</h3>
                    <div>
                        <x-input-label for="digest-algorithm" value="Algoritmo" />
                        <select id="digest-algorithm" x-model="digestAlgorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            @foreach ($algorithms as $id => $algorithm)
                                <option value="{{ $id }}">{{ $algorithm['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="digest-text" value="Texto" />
                        <textarea id="digest-text" x-model="text" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <button type="button" @click="digest()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Calcular hash</button>

                    <div x-show="digestResult" x-cloak class="space-y-2">
                        <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-900">
                            <p><strong>Algoritmo:</strong> <span x-text="digestResult?.label"></span> (<span x-text="digestResult?.bytes"></span> bytes)</p>
                            <p class="mt-2 break-all font-mono text-xs" x-text="digestResult?.hex"></p>
                        </div>
                        <div class="flex gap-3 text-xs">
                            <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="copy(digestResult?.hex, 'digest')">Copiar hex</button>
                            <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="copy(digestResult?.base64, 'digest64')">Copiar Base64</button>
                            <span x-show="copied === 'digest' || copied === 'digest64'" class="text-emerald-600 dark:text-emerald-400">copiado</span>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">HMAC-SHA256</h3>
                    <div>
                        <x-input-label for="hmac-text" value="Mensaje" />
                        <textarea id="hmac-text" x-model="hmacText" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <div>
                        <x-input-label for="hmac-key" value="Llave secreta" />
                        <input id="hmac-key" x-model="hmacKey" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <x-input-label for="hmac-expected" value="HMAC esperado (opcional, para verificar)" />
                        <input id="hmac-expected" x-model="hmacExpected" type="text" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <button type="button" @click="hmac()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Calcular HMAC</button>

                    <div x-show="hmacResult" x-cloak class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-900">
                        <p class="break-all font-mono text-xs" x-text="hmacResult?.result?.hex"></p>
                        <p class="mt-2">Llave usada: <span x-text="hmacResult?.result?.key_bytes"></span> bytes.</p>
                        <p x-show="hmacResult?.valid !== null" class="mt-2 font-semibold" :class="hmacResult?.valid ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" x-text="hmacResult?.valid ? 'HMAC válido: el mensaje no fue alterado.' : 'HMAC inválido: el mensaje o la llave no coinciden.'"></p>
                    </div>
                </section>
            </div>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">bcrypt y Argon2id para contraseñas</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Estos algoritmos incluyen un salt aleatorio dentro del propio hash. Nunca se descifran: se comparan con <code>password_verify()</code>.
                </p>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-3">
                        <div>
                            <x-input-label for="password-algorithm" value="Algoritmo" />
                            <select id="password-algorithm" x-model="passwordAlgorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="argon2id">Argon2id</option>
                                <option value="bcrypt">bcrypt (cost 12)</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="password" value="Contraseña de prueba" />
                            <input id="password" x-model="password" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <button type="button" @click="passwordHash()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Generar hash</button>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <x-input-label for="verify-hash" value="Hash a verificar" />
                            <textarea id="verify-hash" x-model="verifyHash" rows="3" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <div>
                            <x-input-label for="verify-password" value="Contraseña" />
                            <input id="verify-password" x-model="verifyPassword" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <button type="button" @click="verifyPasswordHash()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Verificar</button>
                    </div>
                </div>

                <div x-show="passwordResult" x-cloak class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-900">
                    <p class="break-all font-mono text-xs" x-text="passwordResult?.hash"></p>
                    <p class="mt-2">Algoritmo detectado: <span x-text="passwordResult?.info?.algoName"></span></p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        Costo/parámetros: <span class="font-mono" x-text="JSON.stringify(passwordResult?.info?.options)"></span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">El salt y el costo están incluidos en el hash; no se almacenan por separado.</p>
                </div>
                <div x-show="verifyResult" x-cloak class="rounded-md p-3 text-sm" :class="verifyResult?.valid ? 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100' : 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100'">
                    <span x-text="verifyResult?.message"></span>
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Hash vs cifrado vs Base64</h3>
                    <div class="flex gap-3 text-xs">
                        <button type="button" @click="exportSession('json')" class="underline text-indigo-600 dark:text-indigo-400">Exportar sesión JSON</button>
                        <button type="button" @click="exportSession('csv')" class="underline text-indigo-600 dark:text-indigo-400">Exportar CSV</button>
                    </div>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4">Técnica</th>
                                <th class="py-2 pr-4">¿Reversible?</th>
                                <th class="py-2 pr-4">¿Usa llave?</th>
                                <th class="py-2">Uso típico</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr>
                                <td class="py-3 pr-4 font-semibold">Hash</td>
                                <td class="py-3 pr-4">No</td>
                                <td class="py-3 pr-4">No (HMAC sí)</td>
                                <td class="py-3">Integridad y contraseñas</td>
                            </tr>
                            <tr>
                                <td class="py-3 pr-4 font-semibold">Cifrado</td>
                                <td class="py-3 pr-4">Sí, con la llave</td>
                                <td class="py-3 pr-4">Sí</td>
                                <td class="py-3">Confidencialidad</td>
                            </tr>
                            <tr>
                                <td class="py-3 pr-4 font-semibold">Base64</td>
                                <td class="py-3 pr-4">Sí, sin llave</td>
                                <td class="py-3 pr-4">No</td>
                                <td class="py-3">Codificar datos para transporte</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
