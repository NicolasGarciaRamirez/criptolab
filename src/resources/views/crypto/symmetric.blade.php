<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cifrado simétrico
            </h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="symmetricLab({
            catalog: @js($catalog),
            routes: @js([
                'keygen' => route('crypto.symmetric.key'),
                'encrypt' => route('crypto.symmetric.encrypt'),
                'decrypt' => route('crypto.symmetric.decrypt'),
                'iv' => route('crypto.symmetric.iv'),
                'derive' => route('crypto.symmetric.derive'),
            ])
        })"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                En cifrado simétrico la misma llave cifra y descifra. El IV/nonce se genera con <code>random_bytes()</code> en cada operación y nunca se reutiliza.
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-5">
                    <div>
                        <x-input-label for="algorithm" value="Algoritmo" />
                        <select id="algorithm" x-model="algorithm" @change="onAlgorithmChange()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            @foreach ($catalog as $cipher)
                                <option value="{{ $cipher['id'] }}">{{ $cipher['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="selected?.description"></p>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                Llave: <span x-text="selected?.metadata?.key_bytes"></span> bytes
                            </span>
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                Nonce/IV: <span x-text="selected?.metadata?.nonce_bytes ?? selected?.metadata?.iv_bytes"></span> bytes
                            </span>
                            <span class="rounded-full px-2 py-1" :class="selected?.metadata?.authenticated ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'">
                                <span x-text="selected?.metadata?.authenticated ? 'Autenticado' : 'Sin autenticación'"></span>
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="key" value="Llave (Base64 o hexadecimal)" />
                            <button type="button" @click="generateKey()" class="text-xs underline text-indigo-600 dark:text-indigo-400" :disabled="loading">
                                Generar llave
                            </button>
                        </div>
                        <textarea id="key" x-model="key" rows="2" spellcheck="false" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            <span x-text="keyBytes"></span> bytes ·
                            <button type="button" class="underline" @click="copy(keyHex, 'hex')">copiar hex</button>
                            <span x-show="copied === 'hex'" class="text-emerald-600">copiado</span>
                        </p>
                    </div>

                    <details class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                        <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">Derivar llave desde una frase (PBKDF2 / Argon2id)</summary>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-input-label for="kdf-method" value="Método" />
                                <select id="kdf-method" x-model="kdfMethod" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="pbkdf2">PBKDF2-SHA256</option>
                                    <option value="argon2id">Argon2id (Sodium)</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="kdf-bytes" value="Tamaño de llave" />
                                <select id="kdf-bytes" x-model.number="kdfBytes" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option :value="16">16 bytes</option>
                                    <option :value="32">32 bytes</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="kdf-passphrase" value="Frase de paso" />
                                <input id="kdf-passphrase" x-model="passphrase" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="kdf-salt" value="Salt (vacío = aleatorio)" />
                                <input id="kdf-salt" x-model="salt" type="text" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <x-input-label for="kdf-iterations" value="Iteraciones PBKDF2" />
                                <input id="kdf-iterations" x-model.number="iterations" type="number" min="1000" max="1000000" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            </div>
                        </div>
                        <button type="button" @click="deriveKey()" class="mt-3 rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700" :disabled="loading">
                            Derivar y usar esta llave
                        </button>
                        <div x-show="derivation" x-cloak class="mt-3 rounded bg-gray-50 p-2 text-xs dark:bg-gray-900">
                            <p>Salt: <code x-text="derivation?.salt"></code></p>
                            <p x-show="derivation?.iterations">Iteraciones: <span x-text="derivation?.iterations"></span></p>
                            <p x-show="derivation?.opslimit">opslimit: <span x-text="derivation?.opslimit"></span> · memlimit: <span x-text="derivation?.memlimit"></span></p>
                        </div>
                    </details>

                    <div>
                        <x-input-label for="plaintext" value="Texto en claro" />
                        <textarea id="plaintext" x-model="plaintext" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="encrypt()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Cifrar</button>
                        <button type="button" @click="demonstrateIv()" class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700" :disabled="loading">Demostrar IV aleatorio</button>
                    </div>

                    <div>
                        <x-input-label for="payload" value="Sobre cifrado (JSON) para descifrar" />
                        <textarea id="payload" x-model="payload" rows="4" spellcheck="false" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        <button type="button" @click="decrypt()" class="mt-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Descifrar</button>
                    </div>

                    <div x-show="error" x-cloak class="rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Resultado</h3>
                        <button type="button" @click="copy(resultJson, 'resultado')" class="text-xs underline text-indigo-600 dark:text-indigo-400">Copiar JSON</button>
                    </div>
                    <p x-show="copied === 'resultado'" x-cloak class="text-xs text-emerald-600">Resultado copiado.</p>

                    <template x-if="!result">
                        <p class="mt-4 text-sm text-gray-500">Aún no hay resultados. Genera una llave y pulsa Cifrar.</p>
                    </template>

                    <div x-show="result" x-cloak class="mt-4 space-y-4">
                        <div class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-900">
                            <p><strong>Algoritmo:</strong> <span x-text="result?.label ?? result?.algorithm"></span></p>
                            <p x-show="result?.plaintext !== undefined"><strong>Texto descifrado:</strong> <span x-text="result?.plaintext"></span></p>
                            <p x-show="result?.different_iv !== undefined">
                                <strong>IV/nonce distinto:</strong>
                                <span x-text="result?.different_iv ? 'sí' : 'no'"></span>
                                · <strong>Texto cifrado distinto:</strong>
                                <span x-text="result?.different_ciphertext ? 'sí' : 'no'"></span>
                            </p>
                            <p x-show="result?.sizes">
                                Tamaños: claro <span x-text="(result?.sizes?.plaintext ?? 0) + ' B'"></span>,
                                IV <span x-text="(result?.sizes?.iv ?? 0) + ' B'"></span>,
                                tag <span x-text="(result?.sizes?.tag ?? 0) + ' B'"></span>,
                                cifrado <span x-text="(result?.sizes?.ciphertext ?? 0) + ' B'"></span>.
                            </p>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Salida completa</h4>
                            <pre class="mt-2 max-h-96 overflow-auto rounded-md bg-gray-950 p-3 text-xs text-gray-100" x-text="resultJson"></pre>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
