<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Retos y autoevaluación</h2>
            <a href="{{ route('crypto.lab') }}" class="text-sm underline text-gray-600 dark:text-gray-400">Volver al laboratorio</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="quizLab({
        module: @js($module),
        modules: @js($modules),
        questions: @js($questions),
        routes: @js(['evaluate' => route('crypto.quiz.evaluate')])
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('crypto.partials.subnav')

            <x-lab-guide
                how="Las respuestas se evalúan en el servidor contra un banco de preguntas; no se guardan intentos ni datos personales."
                title="Retos y autoevaluación"
                objective="Comprobar la comprensión de los conceptos del laboratorio con preguntas y explicaciones inmediatas."
                :steps="[
                    'Elige un módulo.',
                    'Responde las preguntas y pulsa Evaluar.',
                    'Revisa el puntaje y la explicación de cada respuesta.',
                    'Usa Reintentar para volver a responder.',
                ]"
                :expected="[
                    'El puntaje se calcula sobre 5 preguntas.',
                    'Cada respuesta muestra si fue correcta o no y por qué.',
                    'No se guardan intentos ni datos personales en la base de datos.',
                ]"
                teacher="Puede usarse como cierre de clase. El resultado es inmediato y no persistente; pida una captura si necesita evidencia."
                warning="La autoevaluación es formativa y no reemplaza la explicación del docente."
            />

            <div class="flex flex-wrap gap-2">
                @foreach ($modules as $id => $label)
                    <a href="{{ route('crypto.quiz', ['modulo' => $id]) }}"
                       class="rounded-full border px-3 py-1.5 text-xs font-medium {{ $module === $id ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <section class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 space-y-5">
                @foreach ($questions as $question)
                    <div class="rounded-md border border-gray-200 p-4 dark:border-gray-700">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $question['question'] }}</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($question['options'] as $key => $option)
                                <label class="flex cursor-pointer items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" name="q-{{ $question['id'] }}" value="{{ $key }}" x-model="answers['{{ $question['id'] }}']" class="mt-1 rounded border-gray-300 text-indigo-600">
                                    <span><strong class="mr-1">{{ strtoupper($key) }}.</strong>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="evaluate()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" :disabled="loading">Evaluar</button>
                    <button type="button" @click="reset()" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Reintentar</button>
                </div>

                <div x-show="result" x-cloak class="space-y-4">
                    <div class="rounded-md bg-indigo-50 p-4 dark:bg-indigo-950">
                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100" x-text="'Puntaje: ' + result?.score + '/' + result?.total + ' (' + result?.percentage + '%)'"></p>
                    </div>
                    <template x-for="detail in result?.details ?? []" :key="detail.id">
                        <div class="rounded-md border p-3 text-sm" :class="detail.correct ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950' : 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950'">
                            <p class="font-medium" x-text="detail.question"></p>
                            <p class="mt-1 text-xs" x-text="detail.correct ? 'Correcta' : 'Incorrecta. Respuesta correcta: ' + detail.correct_answer?.toUpperCase()"></p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300" x-text="detail.explanation"></p>
                        </div>
                    </template>
                </div>
            </section>

            <div x-show="error" x-cloak class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950 dark:text-red-200" x-text="error"></div>
        </div>
    </div>
</x-app-layout>
