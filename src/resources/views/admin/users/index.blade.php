<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Usuarios</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $total }} cuentas registradas. Los emails y teléfonos se muestran descifrados solo para el administrador.</p>
            </div>
            <a href="{{ route('admin.audit.index') }}" class="rounded-md bg-gray-700 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600">Ver auditoría</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div class="min-w-[240px] flex-1">
                    <x-input-label for="search" value="Buscar por nombre o usuario (el email está cifrado y no es buscable)" />
                    <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$search" />
                </div>
                <x-primary-button>Buscar</x-primary-button>
                @if ($search !== '')
                    <a href="{{ route('admin.users.index') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Limpiar</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">ID</th>
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Email (descifrado)</th>
                                <th class="px-4 py-3">Rol</th>
                                <th class="px-4 py-3">Verificado</th>
                                <th class="px-4 py-3">Alta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $user->id }}</td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                                    <td class="px-4 py-3"><code class="text-xs">{{ $user->username }}</code></td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-1 text-xs {{ $user->isAdmin() ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                            {{ $user->role->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs {{ $user->hasVerifiedEmail() ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $user->hasVerifiedEmail() ? 'Sí' : 'No' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $user->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
