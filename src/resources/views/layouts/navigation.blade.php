<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                        CriptoLab
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Panel
                    </x-nav-link>
                    <x-nav-link :href="route('crypto.lab')" :active="request()->routeIs('crypto.lab') || request()->routeIs('crypto.symmetric') || request()->routeIs('crypto.asymmetric') || request()->routeIs('crypto.hash')">
                        Laboratorio
                    </x-nav-link>
                    <x-nav-link :href="route('crypto.history')" :active="request()->routeIs('crypto.history')">
                        Historial
                    </x-nav-link>
                    <x-nav-link :href="route('crypto.keypairs.index')" :active="request()->routeIs('crypto.keypairs.*')">
                        Llaves
                    </x-nav-link>
                    @if (Auth::user()->isAdmin() && Route::has('admin.users.index'))
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            Usuarios
                        </x-nav-link>
                        <x-nav-link :href="route('admin.audit.index')" :active="request()->routeIs('admin.audit.*')">
                            Auditoría
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->username }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                        <x-dropdown-link :href="route('crypto.lab')">Laboratorio</x-dropdown-link>
                        <x-dropdown-link :href="route('crypto.history')">Historial</x-dropdown-link>
                        <x-dropdown-link :href="route('crypto.keypairs.index')">Mis llaves</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Panel</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.lab')" :active="request()->routeIs('crypto.lab')">Laboratorio</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.symmetric')" :active="request()->routeIs('crypto.symmetric')">Cifrado simétrico</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.asymmetric')" :active="request()->routeIs('crypto.asymmetric')">Cifrado asimétrico</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.hash')" :active="request()->routeIs('crypto.hash')">Hash</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.history')" :active="request()->routeIs('crypto.history')">Historial</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('crypto.keypairs.index')" :active="request()->routeIs('crypto.keypairs.*')">Mis llaves</x-responsive-nav-link>
            @if (Auth::user()->isAdmin() && Route::has('admin.users.index'))
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Usuarios</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.audit.index')" :active="request()->routeIs('admin.audit.*')">Auditoría</x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
