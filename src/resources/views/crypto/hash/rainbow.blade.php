<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Rainbow table frente a Argon2id</h2>
            <a href="{{ route('crypto.hash') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a hash</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="rainbowLab({
        dictionary: @js($dictionary),
        routes: @js(['consult' => route('crypto.hash.rainbow.consult')])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="MD5 y SHA-1 permiten precalcular hashes; Argon2id con salt aleatorio y costo alto hace inviable una tabla precomputada."
                title="Rainbow table frente a Argon2id"
                objective="Comprender por qué hashes rápidos y sin salt son vulnerables a tablas precomputadas, y por qué Argon2id con salt cambia el juego."
                :steps="[
                    'Escribe una contraseña común del diccionario mostrado, por ejemplo admin.',
                    'Pulsa Consultar y observa cómo MD5/SHA-1 se pueden buscar en la tabla local.',
                    'Repite con una contraseña que no esté en el diccionario.',
                    'Observa que Argon2id genera un hash distinto cada vez por el salt aleatorio.',
                ]"
                :expected="[
                    'La contraseña del diccionario se encuentra por su hash.',
                    'Una contraseña desconocida no aparece en la tabla.',
                    'Los dos hashes Argon2id de la misma contraseña son diferentes.',
                ]"
                teacher="Evalúa que el estudiante explique la diferencia entre un hash rápido sin salt y un KDF lento con salt."
                warning="MD5 y SHA-1 se usan aquí solo para demostrar su debilidad. Nunca deben usarse para contraseñas reales."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <div>
                    <x-input-label for="rainbow-password" value="Contraseña de prueba" />
                    <input id="rainbow-password" x-model="password" type="text" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <button type="button" @click="consult()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Consultar</button>

                <div x-show="result" x-cloak class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md bg-red-50 p-4 text-sm text-red-950 dark:bg-red-950 dark:text-red-100">
                        <h3 class="font-semibold">Tabla precomputada</h3>
                        <p class="mt-2">Encontrada: <strong x-text="result?.lookup?.found ? 'sí, posición ' + result.lookup.position : 'no'"></strong></p>
                        <p class="mt-2 break-all">MD5: <code x-text="result?.lookup?.md5"></code></p>
                        <p class="break-all">SHA-1: <code x-text="result?.lookup?.sha1"></code></p>
                        <p class="mt-2 text-xs">Diccionario de <span x-text="result?.lookup?.size"></span> contraseñas comunes.</p>
                    </div>
                    <div class="rounded-md bg-emerald-50 p-4 text-sm text-emerald-950 dark:bg-emerald-950 dark:text-emerald-100">
                        <h3 class="font-semibold">Argon2id con salt</h3>
                        <p class="mt-2 break-all font-mono text-xs" x-text="result?.argon2id?.hash"></p>
                        <p class="mt-2">¿Hash distinto en cada intento? <strong x-text="result?.argon2id?.different_each_time ? 'sí' : 'no'"></strong></p>
                        <p class="text-xs">El salt y el costo están dentro del hash.</p>
                    </div>
                </div>

                <details class="rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <summary class="cursor-pointer font-medium">Ver diccionario educativo</summary>
                    <p class="mt-2 font-mono text-xs text-gray-600 dark:text-gray-400">{{ implode(' · ', $dictionary) }}</p>
                </details>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
