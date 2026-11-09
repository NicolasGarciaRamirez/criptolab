<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Benchmark de algoritmos</h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="benchmarkLab({
        routes: @js(['run' => route('crypto.benchmark.run')])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Cada operación se repite varias veces y se mide con hrtime; el resultado depende de la máquina y de PHP-FPM."
                title="Benchmark de algoritmos"
                objective="Comparar órdenes de magnitud entre cifrado simétrico, hash, contraseñas, RSA, firmas e intercambio de llaves."
                :steps="[
                    'Pulsa Ejecutar benchmark y espera unos segundos.',
                    'Revisa las columnas de iteraciones, tiempo total y operaciones por segundo.',
                    'Compara AES/ChaCha con RSA y con Argon2id.',
                ]"
                :expected="[
                    'Los algoritmos simétricos y los hashes rápidos son mucho más rápidos que RSA y Argon2id.',
                    'Argon2id es lento a propósito; no es un problema de rendimiento, es una defensa.',
                    'El resultado es orientativo y depende de la máquina y de PHP-FPM.',
                ]"
                teacher="Evalúa que el estudiante interprete los órdenes de magnitud y no concluya que “lento” significa “inseguro”."
                warning="No uses estos tiempos como certificación de rendimiento ni como prueba de seguridad."
            />

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-4">
                <button type="button" @click="run()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">
                    <span x-show="!loading">Ejecutar benchmark</span>
                    <span x-show="loading">Ejecutando…</span>
                </button>

                <div x-show="result" x-cloak class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2">Categoría</th>
                                <th class="px-3 py-2">Operación</th>
                                <th class="px-3 py-2">Iteraciones</th>
                                <th class="px-3 py-2">Total</th>
                                <th class="px-3 py-2">Promedio</th>
                                <th class="px-3 py-2">Ops/s</th>
                                <th class="px-3 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="(row, index) in result?.rows ?? []" :key="index">
                                <tr>
                                    <td class="px-3 py-2" x-text="row.category"></td>
                                    <td class="px-3 py-2" x-text="row.operation"></td>
                                    <td class="px-3 py-2" x-text="row.iterations"></td>
                                    <td class="px-3 py-2" x-text="row.total_ms + ' ms'"></td>
                                    <td class="px-3 py-2" x-text="row.average_ms + ' ms'"></td>
                                    <td class="px-3 py-2" x-text="row.operations_per_second"></td>
                                    <td class="px-3 py-2 text-xs" x-text="row.status"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400" x-text="result?.note"></p>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
