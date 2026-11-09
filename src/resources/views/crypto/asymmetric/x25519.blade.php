<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Intercambio de llaves X25519</h2>
            <a href="{{ route('crypto.asymmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a asimétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="x25519Lab({
        routes: @js([
            'generate' => route('crypto.asymmetric.x25519.generate'),
            'shared' => route('crypto.asymmetric.x25519.shared'),
        ])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Cada parte combina su secreto privado con la llave pública del otro; ambos llegan al mismo secreto X25519 y HKDF deriva la llave AES."
                title="Intercambio de llaves X25519"
                objective="Comprender cómo dos partes calculan el mismo secreto compartido sin enviarlo por la red."
                :steps="[
                    'Pulsa Generar pares y revisa las llaves públicas y privadas de Alice y Bob.',
                    'Pulsa Calcular secreto compartido.',
                    'Comprueba que Alice y Bob obtienen el mismo fingerprint y la misma llave derivada.',
                    'Opcionalmente, escribe un mensaje: se cifra con la llave derivada de Alice y se descifra con la de Bob.',
                ]"
                :expected="[
                    'Los dos secretos compartidos son iguales.',
                    'Los fingerprints coinciden.',
                    'El mensaje cifrado con la llave derivada se descifra correctamente.',
                ]"
                teacher="Evalúa que el estudiante relacione Diffie-Hellman con el problema de distribuir llaves y que no confunda el secreto compartido con una llave pública."
                warning="Las llaves privadas se muestran solo en esta sesión; no se guardan en la base de datos."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="generate()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Generar pares</button>
                    <button type="button" @click="shared()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Calcular secreto compartido</button>
                </div>

                <div x-show="alice.public_key" x-cloak class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Alice</h3>
                        <p class="mt-2 text-xs uppercase text-gray-500 dark:text-gray-400">Llave pública</p>
                        <textarea readonly rows="3" x-model="alice.public_key" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        <p class="mt-2 text-xs uppercase text-gray-500 dark:text-gray-400">Llave privada (solo esta sesión)</p>
                        <textarea readonly rows="2" x-model="alice.private_key" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Bob</h3>
                        <p class="mt-2 text-xs uppercase text-gray-500 dark:text-gray-400">Llave pública</p>
                        <textarea readonly rows="3" x-model="bob.public_key" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        <p class="mt-2 text-xs uppercase text-gray-500 dark:text-gray-400">Llave privada (solo esta sesión)</p>
                        <textarea readonly rows="2" x-model="bob.private_key" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    </div>
                </div>

                <div>
                    <x-input-label for="x25519-message" value="Mensaje opcional para cifrar con la llave derivada" />
                    <input id="x25519-message" x-model="message" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div x-show="result" x-cloak class="space-y-3 rounded-md bg-emerald-50 p-4 text-sm text-emerald-950 dark:bg-emerald-950 dark:text-emerald-100">
                    <p><strong>Mismo secreto:</strong> <span x-text="result?.same_secret ? 'sí' : 'no'"></span></p>
                    <p><strong>Misma llave AES:</strong> <span x-text="result?.same_key ? 'sí' : 'no'"></span></p>
                    <p class="break-all">Fingerprint Alice: <code x-text="result?.alice_fingerprint"></code></p>
                    <p class="break-all">Fingerprint Bob: <code x-text="result?.bob_fingerprint"></code></p>
                    <p class="break-all">Llave derivada Alice (Base64): <code x-text="result?.alice_key?.key"></code></p>
                    <p x-show="result?.decrypted"><strong>Mensaje descifrado por Bob:</strong> <span x-text="result?.decrypted"></span></p>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
