@php
    $algorithms = collect($cipherCatalog)
        ->concat($signerCatalog)
        ->map(fn (array $algorithm): array => ['id' => $algorithm['id'], 'label' => $algorithm['label']])
        ->filter(fn (array $algorithm): bool => str_starts_with($algorithm['id'], 'rsa-')
            || str_starts_with($algorithm['id'], 'ecdsa-')
            || $algorithm['id'] === 'hybrid-rsa-aes-256-gcm')
        ->unique('id')
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Importar llaves PEM</h2>
            <a href="{{ route('crypto.asymmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a asimétrico</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="keyImportLab({
        algorithms: @js($algorithms),
        routes: @js(['inspect' => route('crypto.asymmetric.import.inspect')])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Un PEM contiene la llave en Base64 con cabeceras; OpenSSL la interpreta y calcula tipo, bits/curva y fingerprint."
                title="Importar llaves PEM"
                objective="Inspeccionar una llave pública o privada existente, calcular su fingerprint y reutilizarla en el laboratorio."
                :steps="[
                    'Pega la llave pública PEM y, si quieres guardarla, también la privada.',
                    'Elige el contexto del algoritmo (RSA-OAEP, RSA-SHA256, ECDSA, etc.).',
                    'Pulsa Inspeccionar para ver tipo, bits, curva y fingerprint.',
                    'Marca Guardar si quieres almacenar la privada cifrada con la llave maestra.',
                ]"
                :expected="[
                    'El tipo y fingerprint corresponden a la llave pegada.',
                    'La llave pública y privada deben corresponder entre sí.',
                    'Si se guarda, la privada no vuelve a mostrarse en la interfaz.',
                ]"
                teacher="Evalúa que el estudiante relacione PEM, fingerprint y tipo de llave, y que entienda el riesgo de importar llaves privadas."
                warning="No pegues llaves privadas reales. En producción, las llaves deben gestionarse con un KMS/HSM."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <div>
                    <x-input-label for="import-algorithm" value="Contexto del algoritmo" />
                    <select id="import-algorithm" x-model="algorithm" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        @foreach ($algorithms as $algorithm)
                            <option value="{{ $algorithm['id'] }}">{{ $algorithm['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="import-public" value="Llave pública PEM" />
                    <textarea id="import-public" x-model="publicKey" rows="5" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div>
                    <x-input-label for="import-private" value="Llave privada PEM (opcional salvo para guardar)" />
                    <textarea id="import-private" x-model="privateKey" rows="5" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="import-name" value="Nombre" />
                        <input id="import-name" x-model="name" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <label class="flex items-end gap-2 pb-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" x-model="save" class="rounded border-gray-300 text-indigo-600">
                        Guardar llave privada cifrada
                    </label>
                </div>
                <button type="button" @click="inspect()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Inspeccionar</button>

                <div x-show="result" x-cloak class="rounded-md bg-gray-50 p-4 text-sm dark:bg-gray-900">
                    <p><strong>Tipo:</strong> <span x-text="result?.inspection?.type"></span> · <strong>Bits:</strong> <span x-text="result?.inspection?.bits ?? '—'"></span> · <strong>Curva:</strong> <span x-text="result?.inspection?.curve ?? '—'"></span></p>
                    <p class="mt-2 break-all"><strong>Fingerprint:</strong> <code x-text="result?.inspection?.fingerprint"></code></p>
                    <p class="mt-2"><strong>Guardada:</strong> <span x-text="result?.saved ? 'sí (ID ' + result.keypair_id + ')' : 'no'"></span></p>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
