<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mis llaves
            </h2>
            <a href="{{ route('crypto.asymmetric') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Generar una llave nueva</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                Las llaves privadas se guardan cifradas con la llave maestra y nunca se vuelven a mostrar. Solo puedes descargar la llave pública.
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3">Nombre</th>
                                <th class="px-4 py-3">Algoritmo</th>
                                <th class="px-4 py-3">Huella SHA-256</th>
                                <th class="px-4 py-3">Creada</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($keypairs as $keypair)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $keypair->name }}</td>
                                    <td class="px-4 py-3"><code class="text-xs">{{ $keypair->algorithm }}</code></td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($keypair->fingerprint, 24) }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $keypair->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('crypto.keypairs.public', $keypair->id) }}" class="text-xs underline text-indigo-600 dark:text-indigo-400">Descargar pública</a>
                                            <form method="POST" action="{{ route('crypto.keypairs.destroy', $keypair->id) }}" onsubmit="return confirm('¿Eliminar esta llave? Las entradas de demostración que la usen dejarán de descifrarse.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs underline text-red-600 dark:text-red-400">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        Aún no tienes llaves guardadas. Genera una en el laboratorio asimétrico.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $keypairs->links() }}</div>
        </div>
    </div>
</x-app-layout>
