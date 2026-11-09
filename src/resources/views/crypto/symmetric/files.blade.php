<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Cifrado de archivos</h2>
            <a href="{{ route('crypto.symmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a simétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="fileLab({
        catalog: @js($catalog),
        routes: @js([
            'keygen' => route('crypto.symmetric.key'),
            'encrypt' => route('crypto.symmetric.files.encrypt'),
            'decrypt' => route('crypto.symmetric.files.decrypt'),
        ]),
        maxBytes: @js($maxBytes)
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="El archivo se convierte a Base64 dentro de un payload JSON, se cifra en memoria y se descarga; al descifrar se reconstruye el archivo original."
                title="Cifrado de archivos"
                objective="Aplicar cifrado autenticado a un archivo real, sin guardar el original ni el cifrado en el servidor."
                :steps="[
                    'Elige un algoritmo autenticado y genera una llave.',
                    'Selecciona un archivo pequeño (máximo 5 MB) y pulsa Cifrar y descargar.',
                    'Descarga el archivo .crypt generado y observa su tamaño.',
                    'Selecciona el .crypt, usa la misma llave y pulsa Descifrar y descargar.',
                    'Comprueba que el archivo recuperado tiene el mismo contenido y nombre.',
                ]"
                :expected="[
                    'El archivo descifrado es idéntico al original.',
                    'El nombre y el MIME viajan cifrados dentro del payload; no se registran en logs.',
                    'Con una llave distinta o un .crypt alterado, el descifrado falla.',
                ]"
                teacher="Evalúa que el estudiante explique por qué el archivo no se guarda en el servidor y por qué solo se permiten algoritmos autenticados."
                warning="El archivo se procesa en memoria y se descarga; no se conserva en el servidor. Límite: 5 MB."
            />

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Cifrar archivo</h3>
                    <div>
                        <x-input-label for="file-algorithm" value="Algoritmo" />
                        <select id="file-algorithm" x-model="algorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            @foreach ($catalog as $cipher)
                                <option value="{{ $cipher['id'] }}">{{ $cipher['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="selected?.description"></p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="file-key" value="Llave (Base64 o hex)" />
                            <button type="button" @click="generateKey()" class="text-xs underline text-indigo-600 dark:text-indigo-400" :disabled="loading">Generar</button>
                        </div>
                        <textarea id="file-key" x-model="key" rows="2" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <div>
                        <x-input-label for="file-input" value="Archivo (máximo {{ $maxBytes / 1024 / 1024 }} MB)" />
                        <input id="file-input" type="file" @change="pickFile($event)" class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300">
                    </div>
                    <button type="button" @click="encrypt()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Cifrar y descargar</button>
                    <div x-show="result" x-cloak class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-900">
                        <p>Archivo: <span x-text="result?.original_name"></span></p>
                        <p>Original: <span x-text="result?.original_bytes + ' B'"></span> · Ciphertext: <span x-text="result?.ciphertext_bytes + ' B'"></span></p>
                        <button type="button" class="mt-2 text-xs underline text-indigo-600 dark:text-indigo-400" @click="downloadResult()">Volver a descargar .crypt</button>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Descifrar archivo</h3>
                    <div>
                        <x-input-label for="crypt-input" value="Archivo .crypt" />
                        <input id="crypt-input" type="file" accept=".crypt,.json" @change="pickCryptFile($event)" class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300">
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Usa el mismo algoritmo y la misma llave con la que cifraste.</p>
                    <button type="button" @click="decrypt()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Descifrar y descargar</button>
                    <div x-show="decrypted" x-cloak class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                        Archivo recuperado: <span x-text="decrypted?.name"></span> (<span x-text="decrypted?.bytes + ' B'"></span>)
                    </div>
                </section>
            </div>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
