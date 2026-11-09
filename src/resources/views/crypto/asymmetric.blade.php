<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cifrado asimétrico y firmas digitales
            </h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="asymmetricLab({
            cipherCatalog: @js($cipherCatalog),
            signerCatalog: @js($signerCatalog),
            routes: @js([
                'keygen' => route('crypto.asymmetric.keypair'),
                'signerKeygen' => route('crypto.asymmetric.signer.keypair'),
                'encrypt' => route('crypto.asymmetric.encrypt'),
                'decrypt' => route('crypto.asymmetric.decrypt'),
                'sign' => route('crypto.asymmetric.sign'),
                'verify' => route('crypto.asymmetric.verify'),
            ])
        })"
    >
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <div class="flex justify-end gap-3 text-xs">
                <button type="button" @click="exportSession('json')" class="underline text-indigo-600 dark:text-indigo-400">Exportar sesión JSON</button>
                <button type="button" @click="exportSession('csv')" class="underline text-indigo-600 dark:text-indigo-400">Exportar CSV</button>
            </div>

            <x-lab-guide
                how="La llave pública cifra y la privada descifra; la firma usa la privada y se verifica con la pública."
                title="Cifrado asimétrico y firmas digitales"
                objective="Diferenciar llave pública/privada, cifrar y descifrar, firmar y verificar, y entender por qué RSA necesita el modo híbrido."
                :steps="[
                    'Genera un par de llaves RSA, sealed box o híbrido. Guarda la llave pública y conserva la privada solo para esta sesión.',
                    'Cifra un texto con la llave pública y descífralo con la privada.',
                    'Genera un par de firma (Ed25519, RSA-SHA256 o ECDSA), firma un mensaje y verifica la firma.',
                    'Cambia un carácter del mensaje y vuelve a verificar: debe fallar.',
                    'Prueba a alterar el ciphertext y descifrar: debe fallar.',
                ]"
                :expected="[
                    'El texto descifrado coincide con el original.',
                    'La firma válida devuelve true y el mensaje alterado devuelve false.',
                    'RSA-OAEP rechaza mensajes que superan su límite; el híbrido acepta textos largos.',
                    'Las llaves privadas no se muestran de nuevo al recargar la página.',
                ]"
                teacher="Evalúa la diferencia entre cifrar (confidencialidad) y firmar (autenticidad/integridad), y que el estudiante explique el límite de RSA."
                warning="No uses llaves privadas reales en el laboratorio. La passphrase opcional se admite para el PEM generado, pero no se usa para recuperar llaves guardadas."
            />

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('crypto.asymmetric.x25519') }}" class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Intercambio X25519</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dos partes obtienen el mismo secreto sin enviarlo.</p>
                </a>
                <a href="{{ route('crypto.asymmetric.sign-encrypt') }}" class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Firma + cifrado</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Firmar y luego cifrar en un solo flujo.</p>
                </a>
                <a href="{{ route('crypto.asymmetric.certificates') }}" class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Certificados X.509</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Generar y verificar un certificado autofirmado.</p>
                </a>
                <a href="{{ route('crypto.asymmetric.import') }}" class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-gray-100">Importar llaves PEM</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Inspeccionar, calcular fingerprint y guardar si se solicita.</p>
                </a>
            </div>

            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                La llave privada se muestra una sola vez. Si marcas "guardar", el sistema la almacena cifrada con la llave maestra, pero no volverá a mostrarla.
            </div>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">1. Generar par de llaves</h3>
                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                    <div>
                        <x-input-label for="key-algorithm" value="Algoritmo de cifrado" />
                        <select id="key-algorithm" x-model="keyAlgorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            @foreach ($cipherCatalog as $cipher)
                                <option value="{{ $cipher['id'] }}">{{ $cipher['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="key-name" value="Nombre (opcional)" />
                        <input id="key-name" x-model="keyName" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="Mis llaves de práctica">
                    </div>
                    <div>
                        <x-input-label for="key-passphrase" value="Passphrase (opcional)" />
                        <input id="key-passphrase" x-model="keyPassphrase" type="password" autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" x-model="saveKey" class="rounded border-gray-300 text-indigo-600">
                    Guardar la llave pública y la privada cifrada en mi cuenta
                </label>
                <div class="mt-3">
                    <button type="button" @click="generateKeypair()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="keyLoading">Generar par</button>
                </div>

                <div x-show="keyError" x-cloak class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="keyError"></div>

                <div x-show="pair.public_key" x-cloak class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label value="Llave pública" />
                            <div class="flex gap-3 text-xs">
                                <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="copy(pair?.public_key, 'publica')">Copiar</button>
                                <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="download('llave-publica.txt', pair?.public_key)">Descargar</button>
                                <span x-show="copied === 'publica'" class="text-emerald-600 dark:text-emerald-400">copiada</span>
                            </div>
                        </div>
                        <textarea readonly rows="10" x-model="pair.public_key" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label value="Llave privada (se muestra una sola vez)" />
                            <div class="flex gap-3 text-xs">
                                <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="copy(pair?.private_key, 'privada')">Copiar</button>
                                <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="download('llave-privada.txt', pair?.private_key)">Descargar</button>
                                <span x-show="copied === 'privada'" class="text-emerald-600 dark:text-emerald-400">copiada</span>
                            </div>
                        </div>
                        <textarea readonly rows="10" x-model="pair.private_key" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400" x-show="saveKey">Guardada cifrada en la base de datos. La passphrase, si la usaste, no se almacena.</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Huella SHA-256: <span class="font-mono" x-text="pair?.fingerprint"></span></p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">2. Cifrar con llave pública</h3>
                    <div>
                        <x-input-label for="encrypt-algorithm" value="Algoritmo" />
                        <select id="encrypt-algorithm" x-model="encryptAlgorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            @foreach ($cipherCatalog as $cipher)
                                <option value="{{ $cipher['id'] }}">{{ $cipher['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Límite de este algoritmo: <span x-text="maxBytes ? maxBytes + ' bytes' : 'sin límite práctico'"></span>. Para textos largos usa el híbrido.</p>
                    </div>
                    <div>
                        <x-input-label for="asym-plaintext" value="Texto en claro" />
                        <textarea id="asym-plaintext" x-model="plaintext" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <div>
                        <x-input-label for="asym-public-key" value="Llave pública" />
                        <textarea id="asym-public-key" x-model="publicKey" rows="4" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <button type="button" @click="encrypt()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="operationLoading">Cifrar</button>

                    <div x-show="operationError" x-cloak class="rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="operationError"></div>

                    <div x-show="encrypted" x-cloak class="space-y-2">
                        <x-input-label value="Sobre cifrado (JSON)" />
                        <textarea rows="5" x-model="payload" class="block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Tamaño del ciphertext: <span x-text="(encrypted?.sizes?.ciphertext ?? 0) + ' B'"></span></p>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">3. Descifrar con llave privada</h3>
                    <div>
                        <x-input-label for="decrypt-private-key" value="Llave privada" />
                        <textarea id="decrypt-private-key" x-model="privateKey" rows="6" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    </div>
                    <div>
                        <x-input-label for="decrypt-passphrase" value="Passphrase (si la llave está protegida)" />
                        <input id="decrypt-passphrase" x-model="decryptPassphrase" type="password" autocomplete="off" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="decrypt()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="operationLoading">Descifrar</button>
                        <button type="button" @click="tamperAndDecrypt()" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700" :disabled="operationLoading">Alterar ciphertext y descifrar</button>
                    </div>

                    <div x-show="decrypted" x-cloak class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                        <p class="font-semibold">Texto descifrado:</p>
                        <p class="mt-1 whitespace-pre-wrap" x-text="decrypted"></p>
                    </div>
                </section>
            </div>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">4. Firma digital</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Firmar no cifra: demuestra autenticidad e integridad. Si cambias un solo carácter, la verificación falla.</p>

                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    <div class="space-y-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-input-label for="signer-algorithm" value="Algoritmo de firma" />
                                <select id="signer-algorithm" x-model="signerAlgorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    @foreach ($signerCatalog as $signer)
                                        <option value="{{ $signer['id'] }}">{{ $signer['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="button" @click="generateSignerKeypair()" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600" :disabled="signerLoading">Generar llaves de firma</button>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="sign-message" value="Mensaje a firmar" />
                            <textarea id="sign-message" x-model="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <div>
                            <x-input-label for="sign-private" value="Llave privada de firma" />
                            <textarea id="sign-private" x-model="signerPrivate" rows="4" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <button type="button" @click="sign()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="signerLoading">Firmar mensaje</button>
                        <div x-show="signature" x-cloak>
                            <x-input-label value="Firma (Base64)" />
                            <textarea rows="3" x-model="signature" class="block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <x-input-label for="verify-message" value="Mensaje a verificar" />
                            <textarea id="verify-message" x-model="verifyMessage" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <div>
                            <x-input-label for="verify-signature" value="Firma" />
                            <textarea id="verify-signature" x-model="verifySignature" rows="3" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <div>
                            <x-input-label for="verify-public" value="Llave pública de firma" />
                            <textarea id="verify-public" x-model="verifyPublic" rows="4" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <button type="button" @click="verify()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="signerLoading">Verificar firma</button>

                        <div x-show="verifyResult" x-cloak class="rounded-md p-3 text-sm" :class="verifyResult?.valid ? 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100' : 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-100'">
                            <span x-text="verifyResult?.message"></span>
                        </div>
                    </div>
                </div>
                <div x-show="signerError" x-cloak class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="signerError"></div>
            </section>
        </div>
    </div>
</x-app-layout>
