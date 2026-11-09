<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Modos AES y reutilización de nonce</h2>
            <a href="{{ route('crypto.symmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a simétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="aesModesLab({
        routes: @js([
            'compare' => route('crypto.symmetric.modes.compare'),
            'nonce' => route('crypto.symmetric.modes.nonce'),
        ])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="ECB cifra cada bloque por separado; CBC encadena bloques con un IV; CTR usa un contador; GCM agrega un tag de autenticación."
                title="Comparación de modos AES y nonce reutilizado"
                objective="Comprender por qué ECB revela patrones, por qué CBC/CTR no autentican y por qué reutilizar el nonce rompe la seguridad."
                :steps="[
                    'En Comparación de modos, escribe un texto con bloques repetidos y pulsa Comparar.',
                    'Revisa los bloques repetidos de ECB frente a CBC/CTR/GCM.',
                    'En la segunda tarjeta, elige AES-GCM o ChaCha20, escribe dos textos de igual longitud y pulsa el demo inseguro.',
                    'Observa que ambos usan el mismo nonce y que el XOR de los ciphertexts coincide con el XOR de los textos claros.',
                ]"
                :expected="[
                    'ECB muestra más bloques repetidos que CBC/CTR/GCM.',
                    'GCM incluye un tag de autenticación; ECB/CBC/CTR no.',
                    'El demo de nonce reutilizado muestra la fuga XOR.',
                ]"
                teacher="Evalúa que el estudiante identifique ECB como inseguro y explique por qué el nonce nunca debe reutilizarse."
                warning="ECB y la reutilización de nonce son demos deliberadamente inseguros. Nunca deben usarse para datos reales."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Comparación: ECB, CBC, CTR y GCM</h3>
                <div>
                    <x-input-label for="modes-plaintext" value="Texto (incluye bloques repetidos)" />
                    <textarea id="modes-plaintext" x-model="plaintext" rows="3" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <button type="button" @click="compare()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Comparar modos</button>

                <div x-show="comparison" x-cloak class="overflow-x-auto">
                    <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Llave compartida: <code x-text="comparison?.key_hex"></code></p>
                    <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-700">
                        <thead>
                            <tr class="text-left uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-3">Modo</th>
                                <th class="py-2 pr-3">IV/nonce</th>
                                <th class="py-2 pr-3">Tag</th>
                                <th class="py-2 pr-3">Bloques repetidos</th>
                                <th class="py-2 pr-3">Ciphertext (hex, inicio)</th>
                                <th class="py-2">Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="mode in comparison?.modes ?? []" :key="mode.id">
                                <tr>
                                    <td class="py-2 pr-3 font-semibold" x-text="mode.label"></td>
                                    <td class="py-2 pr-3 font-mono" x-text="mode.iv_hex ?? '—'"></td>
                                    <td class="py-2 pr-3 font-mono" x-text="mode.tag_hex ?? '—'"></td>
                                    <td class="py-2 pr-3" x-text="mode.repeated_blocks"></td>
                                    <td class="max-w-xs break-all py-2 pr-3 font-mono" x-text="mode.ciphertext_hex.slice(0, 96) + '…'"></td>
                                    <td class="py-2" x-text="mode.warning"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg border-2 border-amber-300 bg-white p-6 shadow-sm dark:border-amber-700 dark:bg-gray-800 space-y-4">
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Demo inseguro: mismo nonce dos veces</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <x-input-label for="nonce-algorithm" value="Algoritmo" />
                        <select id="nonce-algorithm" x-model="algorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="aes-256-gcm">AES-256-GCM</option>
                            <option value="chacha20-poly1305">ChaCha20-Poly1305</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="nonce-first" value="Primer texto" />
                        <input id="nonce-first" x-model="first" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <x-input-label for="nonce-second" value="Segundo texto (misma longitud)" />
                        <input id="nonce-second" x-model="second" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <button type="button" @click="demonstrateNonceReuse()" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700" :disabled="loading">Ejecutar demo inseguro</button>

                <div x-show="nonce" x-cloak class="space-y-2 rounded-md bg-amber-50 p-4 text-sm text-amber-950 dark:bg-amber-950 dark:text-amber-100">
                    <p>Nonce reutilizado: <code x-text="nonce?.nonce_hex"></code></p>
                    <p>XOR ciphertexts: <code class="break-all" x-text="nonce?.xor_ciphertexts_hex"></code></p>
                    <p>XOR textos claros: <code class="break-all" x-text="nonce?.xor_plaintexts_hex"></code></p>
                    <p class="font-semibold" x-text="nonce?.leak_matches ? 'Los XOR coinciden: la relación entre los textos claros quedó expuesta.' : 'Los XOR no coinciden.'"></p>
                    <p class="text-xs" x-text="nonce?.warning"></p>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
