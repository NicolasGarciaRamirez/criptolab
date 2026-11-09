<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Certificados X.509 autofirmados</h2>
            <a href="{{ route('crypto.asymmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a asimétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="certificateLab({
        routes: @js([
            'generate' => route('crypto.asymmetric.certificates.generate'),
            'inspect' => route('crypto.asymmetric.certificates.inspect'),
        ])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Se genera una llave, un CSR y se firma con la misma llave; por eso el sujeto y el emisor coinciden en un certificado autofirmado."
                title="Certificados X.509 autofirmados"
                objective="Relacionar una llave pública con una identidad y verificar la firma de un certificado."
                :steps="[
                    'Escribe un Common Name, organización y país.',
                    'Genera el certificado autofirmado y revisa sujeto, emisor, validez y fingerprint.',
                    'Descarga el certificado, la llave privada y el CSR si los necesitas.',
                    'Pega un certificado PEM en la sección Inspeccionar y verifica su firma autofirmada.',
                ]"
                :expected="[
                    'El certificado se genera con sujeto y emisor iguales (autofirmado).',
                    'La verificación devuelve true.',
                    'El fingerprint SHA-256 es estable para el mismo certificado.',
                ]"
                teacher="Evalúa que el estudiante diferencie un certificado autofirmado de uno emitido por una CA y que explique para qué sirve el CSR."
                warning="Un certificado autofirmado no es confiable para producción. La llave privada se muestra una sola vez."
            />

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Generar certificado</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="cert-cn" value="Common Name" />
                            <input id="cert-cn" x-model="commonName" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <x-input-label for="cert-org" value="Organización" />
                            <input id="cert-org" x-model="organization" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <x-input-label for="cert-country" value="País (2 letras)" />
                            <input id="cert-country" x-model="country" type="text" maxlength="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <x-input-label for="cert-days" value="Días de validez" />
                            <input id="cert-days" x-model.number="days" type="number" min="1" max="3650" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                    <button type="button" @click="generate()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Generar certificado</button>

                    <div x-show="certificate" x-cloak class="space-y-3">
                        <div class="rounded-md bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
                            <p><strong>Verificado:</strong> <span x-text="certificate?.verified ? 'sí' : 'no'"></span></p>
                            <p class="break-all"><strong>Fingerprint:</strong> <code x-text="certificate?.fingerprint"></code></p>
                            <p><strong>Sujeto:</strong> <span x-text="certificate?.parsed?.subject?.CN"></span></p>
                            <p><strong>Válido:</strong> <span x-text="certificate?.parsed?.valid_from"></span> → <span x-text="certificate?.parsed?.valid_to"></span></p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs">
                            <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="downloadPem('certificado.pem', certificate.certificate)">Descargar certificado</button>
                            <button type="button" class="underline text-amber-700 dark:text-amber-300" @click="downloadPem('llave-privada.pem', certificate.private_key)">Descargar llave privada</button>
                            <button type="button" class="underline text-indigo-600 dark:text-indigo-400" @click="downloadPem('solicitud.csr', certificate.csr)">Descargar CSR</button>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Inspeccionar certificado</h3>
                    <textarea x-model="inspectPem" rows="8" class="block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="-----BEGIN CERTIFICATE-----"></textarea>
                    <button type="button" @click="inspect()" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" :disabled="loading">Inspeccionar</button>
                    <div x-show="inspected" x-cloak class="rounded-md bg-gray-50 p-3 text-sm dark:bg-gray-900">
                        <pre class="max-h-72 overflow-auto text-xs" x-text="JSON.stringify(inspected, null, 2)"></pre>
                    </div>
                </section>
            </div>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
