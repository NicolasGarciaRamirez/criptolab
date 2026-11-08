<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Registro de actividad</h2>
                <p class="text-sm text-gray-500">Auditoría de acciones relevantes. Nunca se guardan contraseñas, textos claros ni llaves privadas.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600">Ver usuarios</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div>
                    <x-input-label for="action" value="Acción" />
                    <select id="action" name="action" class="mt-1 block rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">Todas</option>
                        @foreach ($actions as $item)
                            <option value="{{ $item }}" @selected($action === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Filtrar</x-primary-button>
                @if ($action !== '')
                    <a href="{{ route('admin.audit.index') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Limpiar</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Acción</th>
                                <th class="px-4 py-3">Descripción</th>
                                <th class="px-4 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 text-gray-500">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-3">
                                        @if ($log->user)
                                            <span class="font-mono text-xs">{{ $log->user->username }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs dark:bg-gray-700">{{ $log->actionLabel() }}</span>
                                        <span class="block text-[10px] text-gray-400">{{ $log->action }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $log->description }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $log->ip_address }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No hay registros para el filtro seleccionado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
