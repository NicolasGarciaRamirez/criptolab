<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Firma + cifrado</h2>
            <a href="{{ route('crypto.asymmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a asimétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="signEncryptLab({
        signerCatalog: @js($signerCatalog),
        cipherCatalog: @js($cipherCatalog),
        routes: @js([
            'keys' => route('crypto.asymmetric.sign-encrypt.keys'),
            'encrypt' => route('crypto.asymmetric.sign-encrypt.encrypt'),
            'decrypt' => route('crypto.asymmetric.sign-encrypt.decrypt'),
        ])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Primero se firma el mensaje con la llave privada de firma; después el paquete firmado se cifra con la llave pública de cifrado."
                title="Firma y cifrado combinados"
                objective="Combinar autenticidad e integridad de una firma con la confidencialidad del cifrado híbrido."
                :steps="[
                    'Pulsa Generar pares: se crea un par Ed25519 para firmar y un par híbrido RSA+AES para cifrar.',
                    'Escribe un mensaje y pulsa Firmar y cifrar.',
                    'Copia el paquete cifrado y pulsa Descifrar y verificar.',
                    'Comprueba que el mensaje coincide y que la firma es válida.',
                ]"
                :expected="[
                    'El mensaje descifrado coincide con el original.',
                    'La verificación de firma devuelve true.',
                    'Si alteras el paquete cifrado, el descifrado falla; si alteras el mensaje, la firma falla.',
                ]"
                teacher="Evalúa que el estudiante explique el orden firma→cifrado y por qué se necesita la llave pública del firmante para verificar."
                warning="Los pares de llaves se muestran una sola vez en esta pantalla y no se guardan."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <button type="button" @click="generate()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Generar pares</button>

                <div x-show="signing.public_key && encryption.public_key" x-cloak class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Firma: <span x-text="signing?.label"></span></h3>
                        <textarea readonly rows="3" x-model="signing.public_key" class="mt-2 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        <textarea readonly rows="2" x-model="signing.private_key" class="mt-2 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Cifrado: <span x-text="encryption?.label"></span></h3>
                        <textarea readonly rows="3" x-model="encryption.public_key" class="mt-2 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                        <textarea readonly rows="2" x-model="encryption.private_key" class="mt-2 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>
                    </div>
                </div>

                <div>
                    <x-input-label for="sign-encrypt-message" value="Mensaje" />
                    <textarea id="sign-encrypt-message" x-model="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <button type="button" @click="signAndEncrypt()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Firmar y cifrar</button>

                <div x-show="payload" x-cloak>
                    <x-input-label value="Paquete cifrado" />
                    <textarea rows="4" x-model="payload" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <button type="button" @click="decryptAndVerify()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Descifrar y verificar</button>

                <div x-show="result?.signature_valid !== undefined" x-cloak class="rounded-md p-4 text-sm" :class="result?.signature_valid ? 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100' : 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100'">
                    <p><strong>Mensaje:</strong> <span x-text="result?.message"></span></p>
                    <p><strong>Firma válida:</strong> <span x-text="result?.signature_valid ? 'sí' : 'no'"></span></p>
                    <p><strong>Firmante:</strong> <span x-text="result?.signer_algorithm"></span></p>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
