<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Formulario de autenticación cifrado
                </h2>
                <p class="text-sm text-gray-500">Usuario con cifrado simétrico y contraseña con cifrado asimétrico (configurable).</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('crypto.auth-demo.export') }}" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600">Exportar JSON</a>
                <a href="{{ route('crypto.lab') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Laboratorio</a>
            </div>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="authDemoPage({ decryptTemplate: @js(url('/lab/autenticacion-cifrada/__ID__/descifrar')) })"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border-2 border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-700 dark:bg-red-950 dark:text-red-100">
                <strong>Aviso importante:</strong> esta pantalla es solo educativa. Mostrar contraseñas en claro NUNCA debe hacerse en un sistema real. El login real de CriptoLab guarda únicamente hashes Argon2id.
            </div>

            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registrar credenciales de demostración</h3>
                <form method="POST" action="{{ route('crypto.auth-demo.store') }}" class="mt-4 space-y-4">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="username" value="Usuario (texto en claro)" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', 'ana.estudiante')" required autocomplete="off" />
                            <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password" value="Contraseña (texto en claro, solo demostración)" />
                            <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" :value="old('password', 'ClaveDemo#2026')" required autocomplete="off" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="sym_algorithm" value="Algoritmo simétrico" />
                            <select id="sym_algorithm" name="sym_algorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                @foreach ($symmetricCatalog as $cipher)
                                    <option value="{{ $cipher['id'] }}" @selected(old('sym_algorithm', 'aes-256-gcm') === $cipher['id'])>{{ $cipher['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('sym_algorithm')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="asym_algorithm" value="Algoritmo asimétrico" />
                            <select id="asym_algorithm" name="asym_algorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                @foreach ($asymmetricCatalog as $cipher)
                                    <option value="{{ $cipher['id'] }}" @selected(old('asym_algorithm', 'rsa-oaep-2048') === $cipher['id'])>{{ $cipher['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asym_algorithm')" class="mt-2" />
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="swap" value="1" @checked(old('swap')) class="rounded border-gray-300 text-indigo-600">
                        Intercambiar familias (usuario asimétrico, contraseña simétrica)
                    </label>
                    <p class="text-xs text-gray-500">Por defecto: usuario simétrico y contraseña asimétrica. RSA-OAEP 2048 admite hasta 214 bytes de mensaje; para contraseñas cortas es suficiente.</p>

                    <div>
                        <x-primary-button>Guardar y cifrar</x-primary-button>
                    </div>
                </form>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>

            <section x-show="decrypted" x-cloak class="rounded-lg border border-emerald-300 bg-emerald-50 p-6 dark:border-emerald-700 dark:bg-emerald-950">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">Resultado del descifrado</h3>
                    <button type="button" class="text-sm underline text-emerald-800 dark:text-emerald-200" @click="decrypted = null">Cerrar</button>
                </div>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Usuario descifrado</p>
                        <p class="mt-1 break-all font-mono text-sm text-emerald-950 dark:text-emerald-100" x-text="decrypted?.username"></p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Contraseña descifrada</p>
                        <p class="mt-1 break-all font-mono text-sm text-emerald-950 dark:text-emerald-100" x-text="decrypted?.password"></p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-emerald-800 dark:text-emerald-200">La operación se realizó con las llaves guardadas del usuario autenticado. No se muestra ninguna llave privada.</p>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Registros creados</h3>
                    <p class="text-xs text-gray-500">Texto claro a la izquierda · ciphertext a la derecha</p>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="px-3 py-3">Usuario claro</th>
                                <th class="px-3 py-3">Usuario cifrado</th>
                                <th class="px-3 py-3">Password claro</th>
                                <th class="px-3 py-3">Password cifrado</th>
                                <th class="px-3 py-3">Algoritmos</th>
                                <th class="px-3 py-3">Fecha</th>
                                <th class="px-3 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($entries as $entry)
                                <tr>
                                    <td class="px-3 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $entry->username_plain }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-start gap-2">
                                            <code class="max-w-xs break-all text-xs text-gray-500" title="{{ $entry->username_encrypted }}">{{ \Illuminate\Support\Str::limit($entry->username_encrypted, 90) }}</code>
                                            <button type="button" class="text-xs underline text-indigo-600" @click="copy(@js($entry->username_encrypted), 'u{{ $entry->id }}')">copiar</button>
                                        </div>
                                        <p class="mt-1 text-[10px] text-gray-400">{{ strlen($entry->username_encrypted) }} B</p>
                                    </td>
                                    <td class="px-3 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $entry->password_plain }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-start gap-2">
                                            <code class="max-w-xs break-all text-xs text-gray-500" title="{{ $entry->password_encrypted }}">{{ \Illuminate\Support\Str::limit($entry->password_encrypted, 90) }}</code>
                                            <button type="button" class="text-xs underline text-indigo-600" @click="copy(@js($entry->password_encrypted), 'p{{ $entry->id }}')">copiar</button>
                                        </div>
                                        <p class="mt-1 text-[10px] text-gray-400">{{ strlen($entry->password_encrypted) }} B</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs">
                                        <div><span class="font-semibold">sim:</span> {{ $entry->sym_algorithm }}</div>
                                        <div><span class="font-semibold">asym:</span> {{ $entry->asym_algorithm }}</div>
                                        <div class="mt-1 text-[10px] text-gray-500">
                                            {{ ($entry->meta['swap'] ?? false) ? 'invertido' : 'usuario simétrico / password asimétrico' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-gray-500">{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-col items-end gap-2">
                                            <button type="button" class="text-xs underline text-emerald-600" @click="decrypt({{ $entry->id }})" :disabled="decryptingId === {{ $entry->id }}">
                                                <span x-show="decryptingId !== {{ $entry->id }}">Descifrar</span>
                                                <span x-show="decryptingId === {{ $entry->id }}">Descifrando…</span>
                                            </button>
                                            <form method="POST" action="{{ route('crypto.auth-demo.destroy', $entry->id) }}" onsubmit="return confirm('¿Eliminar este registro de demostración?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs underline text-red-600">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">Aún no hay registros. Completa el formulario para crear el primero.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p x-show="copied" x-cloak class="mt-2 text-xs text-emerald-600">Texto cifrado copiado (<span x-text="copied"></span>).</p>

                <div class="mt-4">{{ $entries->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
