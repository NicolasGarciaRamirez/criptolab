@props([
    'title',
    'objective',
    'steps' => [],
    'expected' => [],
    'teacher' => null,
    'warning' => null,
    'how' => null,
])

<details open class="rounded-lg border border-indigo-200 bg-indigo-50/70 p-4 text-sm dark:border-indigo-800 dark:bg-indigo-950/40">
    <summary class="cursor-pointer font-semibold text-indigo-900 dark:text-indigo-100">
        Instructivo · {{ $title }}
    </summary>

    <div class="mt-4 space-y-4 text-gray-700 dark:text-gray-300">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Objetivo</p>
            <p class="mt-1">{{ $objective }}</p>
        </div>

        @if ($how)
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Cómo funciona</p>
                <p class="mt-1">{{ $how }}</p>
            </div>
        @endif

        @if (count($steps) > 0)
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Pasos</p>
                <ol class="mt-1 list-decimal space-y-1 pl-5">
                    @foreach ($steps as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if (count($expected) > 0)
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Resultado esperado</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    @foreach ($expected as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($teacher)
            <div class="rounded-md border border-indigo-200 bg-white/70 p-3 dark:border-indigo-800 dark:bg-gray-900/60">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Para el docente</p>
                <p class="mt-1">{{ $teacher }}</p>
            </div>
        @endif

        @if ($warning)
            <div class="rounded-md border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                <span class="font-semibold">Advertencia:</span> {{ $warning }}
            </div>
        @endif
    </div>
</details>
