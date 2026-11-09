@php
    $links = [
        ['route' => 'crypto.lab', 'label' => 'Laboratorio', 'active' => 'crypto.lab'],
        ['route' => 'crypto.symmetric', 'label' => 'Simétrico', 'active' => 'crypto.symmetric'],
        ['route' => 'crypto.symmetric.files', 'label' => 'Archivos', 'active' => 'crypto.symmetric.files*'],
        ['route' => 'crypto.symmetric.modes', 'label' => 'Modos AES', 'active' => 'crypto.symmetric.modes*'],
        ['route' => 'crypto.symmetric.aad', 'label' => 'AAD', 'active' => 'crypto.symmetric.aad*'],
        ['route' => 'crypto.asymmetric', 'label' => 'Asimétrico', 'active' => 'crypto.asymmetric'],
        ['route' => 'crypto.asymmetric.x25519', 'label' => 'X25519', 'active' => 'crypto.asymmetric.x25519*'],
        ['route' => 'crypto.asymmetric.sign-encrypt', 'label' => 'Firma + cifrado', 'active' => 'crypto.asymmetric.sign-encrypt*'],
        ['route' => 'crypto.asymmetric.certificates', 'label' => 'Certificados', 'active' => 'crypto.asymmetric.certificates*'],
        ['route' => 'crypto.asymmetric.import', 'label' => 'Importar llaves', 'active' => 'crypto.asymmetric.import*'],
        ['route' => 'crypto.hash', 'label' => 'Hash', 'active' => 'crypto.hash'],
        ['route' => 'crypto.hash.timing', 'label' => 'Tiempo seguro', 'active' => 'crypto.hash.timing*'],
        ['route' => 'crypto.hash.rainbow', 'label' => 'Rainbow', 'active' => 'crypto.hash.rainbow*'],
        ['route' => 'crypto.auth-demo', 'label' => '5.4', 'active' => 'crypto.auth-demo*'],
        ['route' => 'crypto.benchmark', 'label' => 'Benchmark', 'active' => 'crypto.benchmark*'],
        ['route' => 'crypto.comparison', 'label' => 'Comparador', 'active' => 'crypto.comparison*'],
        ['route' => 'crypto.quiz', 'label' => 'Retos', 'active' => 'crypto.quiz*'],
    ];
@endphp

<nav class="mb-5 overflow-x-auto">
    <div class="flex min-w-max gap-2 text-xs">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}"
               class="rounded-full border px-3 py-1.5 font-medium transition
                    {{ request()->routeIs($link['active']) ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>
</nav>
