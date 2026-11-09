<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">AES-256-GCM con AAD</h2>
            <a href="{{ route('crypto.symmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a simétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="aadLab({
        routes: @js([
            'keygen' => route('crypto.symmetric.key'),
            'encrypt' => route('crypto.symmetric.aad.encrypt'),
            'decrypt' => route('crypto.symmetric.aad.decrypt'),
        ])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="GCM calcula el tag sobre el ciphertext y el AAD; cambiar cualquiera de los dos invalida la verificación."
                title="AES-GCM con datos asociados (AAD)"
                objective="Autenticar metadatos sin cifrarlos y comprobar que cualquier cambio en el AAD invalida el tag."
                :steps="[
                    'Genera una llave y escribe un AAD, por ejemplo usuario=42;contexto=login.',
                    'Cifra un texto con la llave y el AAD.',
                    'Descifra con el mismo AAD: debe funcionar.',
                    'Cambia un carácter del AAD y vuelve a descifrar: debe fallar.',
                ]"
                :expected="[
                    'El mismo AAD permite descifrar.',
                    'Un AAD distinto produce un error de autenticación aunque el ciphertext no cambie.',
                    'El AAD no está cifrado, pero queda autenticado por el tag.',
                ]"
                teacher="Evalúa que el estudiante explique la diferencia entre datos cifrados y datos autenticados."
                warning="El AAD no es secreto; no pongas contraseñas ni llaves en él."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <div>
                    <div class="flex items-center justify-between">
                        <x-input-label for="aad-key" value="Llave AES-256" />
                        <button type="button" @click="generateKey()" class="text-xs underline text-indigo-600 dark:text-indigo-400" :disabled="loading">Generar</button>
                    </div>
                    <textarea id="aad-key" x-model="key" rows="2" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div>
                    <x-input-label for="aad-value" value="AAD (datos asociados)" />
                    <input id="aad-value" x-model="aad" type="text" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <x-input-label for="aad-plaintext" value="Texto en claro" />
                    <textarea id="aad-plaintext" x-model="plaintext" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <button type="button" @click="encrypt()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Cifrar con AAD</button>

                <div x-show="result" x-cloak>
                    <x-input-label value="Sobre cifrado" />
                    <textarea rows="4" x-model="payload" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="decrypt()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Descifrar con el AAD actual</button>
                </div>

                <div x-show="decrypted" x-cloak class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                    Texto descifrado: <span x-text="decrypted"></span>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
