<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Comparador de tamaños y overhead</h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Cada algoritmo agrega metadatos: RSA expande el ciphertext al tamaño de la llave, mientras AES agrega IV y tag."
                title="Comparador de tamaños y overhead"
                objective="Relacionar el costo de cada algoritmo con el tamaño de llave, nonce, tag y ciphertext."
                :steps="[
                    'Revisa la tabla de algoritmos simétricos, asimétricos y de firma.',
                    'Compara el overhead estimado para un mensaje de 100 bytes.',
                    'Observa cómo RSA cambia el tamaño del ciphertext según los bits de la llave.',
                ]"
                :expected="[
                    'AES-GCM agrega el tag y el IV, pero no expande el mensaje al tamaño de RSA.',
                    'RSA-OAEP produce un ciphertext del tamaño de la llave.',
                    'El cifrado híbrido combina el bajo overhead de AES con la protección de llave de RSA.',
                ]"
                teacher="Evalúa que el estudiante explique por qué no se cifran archivos grandes directamente con RSA."
                warning="Los tamaños son estimaciones educativas para un mensaje de 100 bytes; no sustituyen una medición real."
            />

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-3">Familia</th>
                            <th class="px-3 py-3">Algoritmo</th>
                            <th class="px-3 py-3">Llave</th>
                            <th class="px-3 py-3">IV/nonce</th>
                            <th class="px-3 py-3">Tag</th>
                            <th class="px-3 py-3">Autenticado</th>
                            <th class="px-3 py-3">Ciphertext 100 B</th>
                            <th class="px-3 py-3">Overhead 100 B</th>
                            <th class="px-3 py-3">Nota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-3 py-3 text-gray-600 dark:text-gray-300">{{ $row['family'] }}</td>
                                <td class="px-3 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row['algorithm'] }}</td>
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $row['key_bytes'] }} B</td>
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $row['nonce_bytes'] }} B</td>
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $row['tag_bytes'] }} B</td>
                                <td class="px-3 py-3">
                                    <span class="rounded-full px-2 py-1 text-xs {{ $row['authenticated'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                                        {{ $row['authenticated'] ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $row['sample_100b'] }} B</td>
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $row['overhead_100b'] }} B</td>
                                <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $row['note'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
