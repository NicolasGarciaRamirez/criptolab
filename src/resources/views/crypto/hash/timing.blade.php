<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Comparación en tiempo constante</h2>
            <a href="{{ route('crypto.hash') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver a hash</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="timingLab({
        routes: @js(['compare' => route('crypto.hash.timing.compare')])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="=== puede detenerse en el primer byte distinto; hash_equals recorre toda la cadena para no filtrar información por tiempo."
                title="Comparación con hash_equals"
                objective="Entender por qué una comparación que se detiene en el primer byte diferente puede filtrar información por tiempo."
                :steps="[
                    'Escribe dos valores de la misma longitud; pueden ser iguales o diferir en el último carácter.',
                    'Pulsa Comparar y revisa los milisegundos de === y de hash_equals().',
                    'Repite varias veces: los tiempos son ruidosos, pero la comparación segura recorre toda la cadena.',
                ]"
                :expected="[
                    'Ambas comparaciones devuelven el mismo resultado lógico.',
                    'Los tiempos son orientativos y no deben tomarse como una prueba formal.',
                    'La conclusión es usar hash_equals() para comparar secretos o tokens.',
                ]"
                teacher="Evalúa que el estudiante explique por qué una comparación de contraseñas o tokens nunca debe usar ===."
                warning="Un microbenchmark en PHP-FPM no es una medición científica; la demostración es conceptual."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="timing-first" value="Primer valor" />
                        <input id="timing-first" x-model="first" type="text" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <x-input-label for="timing-second" value="Segundo valor (misma longitud)" />
                        <input id="timing-second" x-model="second" type="text" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <div>
                    <x-input-label for="timing-iterations" value="Iteraciones" />
                    <input id="timing-iterations" x-model.number="iterations" type="number" min="100" max="1000000" class="mt-1 block w-48 rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>
                <button type="button" @click="compare()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Comparar</button>

                <div x-show="result" x-cloak class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">===</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="result?.standard_ms + ' ms'"></p>
                    </div>
                    <div class="rounded-md bg-emerald-50 p-4 dark:bg-emerald-950">
                        <p class="text-xs uppercase text-emerald-700 dark:text-emerald-300">hash_equals()</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-950 dark:text-emerald-100" x-text="result?.hash_equals_ms + ' ms'"></p>
                    </div>
                    <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Mismo valor</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100" x-text="result?.same ? 'sí' : 'no'"></p>
                    </div>
                    <p class="sm:col-span-3 text-xs text-gray-500 dark:text-gray-400" x-text="result?.note"></p>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
