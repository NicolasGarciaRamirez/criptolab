<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Historial criptográfico
            </h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Cada operación registra tipo, algoritmo, contexto, estado y fecha; nunca guarda entradas, salidas ni llaves."
                title="Historial de operaciones"
                objective="Revisar qué operaciones se ejecutaron y comprobar que no se guardan textos claros ni llaves privadas."
                :steps="[
                    'Filtra por tipo o algoritmo.',
                    'Revisa fecha, contexto y estado de cada operación.',
                    'Comprueba que solo aparecen metadatos.',
                ]"
                :expected="[
                    'El historial muestra operaciones exitosas y fallidas.',
                    'No aparecen textos claros, contraseñas ni llaves privadas.',
                ]"
                teacher="Pida al estudiante identificar una operación de cifrado y una de verificación fallida."
                warning="El historial es solo de metadatos; no guarda entradas ni salidas criptográficas."
            />

            <form method="GET" action="{{ route('crypto.history') }}" class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div>
                    <x-input-label for="type" value="Tipo" />
                    <select id="type" name="type" class="mt-1 block rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">Todos</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="algorithm" value="Algoritmo" />
                    <select id="algorithm" name="algorithm" class="mt-1 block rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">Todos</option>
                        @foreach ($algorithms as $algorithm)
                            <option value="{{ $algorithm }}" @selected(request('algorithm') === $algorithm)>{{ $algorithm }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Filtrar</x-primary-button>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Algoritmo</th>
                                <th class="px-4 py-3">Contexto</th>
                                <th class="px-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($operations as $operation)
                                <tr>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $operation->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-3"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs dark:bg-gray-700">{{ $operation->type }}</span></td>
                                    <td class="px-4 py-3"><code class="text-xs">{{ $operation->algorithm }}</code></td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $operation->context }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-semibold {{ $operation->success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $operation->success ? 'OK' : 'Falló' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Todavía no hay operaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                El historial guarda solo metadatos: tipo, algoritmo y fecha. Nunca guarda textos claros, contraseñas ni llaves privadas.
            </p>

            <div>{{ $operations->links() }}</div>
        </div>
    </div>
</x-app-layout>
