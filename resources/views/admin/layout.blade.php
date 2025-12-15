<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Jungle Alert')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.add('-translate-x-full');
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen relative">
        <!-- Overlay pour mobile -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="closeSidebar()"></div>
        
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-gray-800 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
            <div class="p-4 flex justify-between items-center">
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold">Jungle Alert</h1>
                    <p class="text-gray-400 text-xs lg:text-sm">Administration</p>
                </div>
                <button onclick="closeSidebar()" class="lg:hidden text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <nav class="mt-8">
                <a href="{{ route('admin.dashboard') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-home mr-3"></i>
                    <span class="text-sm lg:text-base">Dashboard</span>
                </a>
                <a href="{{ route('admin.users.index') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.users.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-users mr-3"></i>
                    <span class="text-sm lg:text-base">Utilisateurs</span>
                </a>
                <a href="{{ route('admin.products.index') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.products.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-shopping-bag mr-3"></i>
                    <span class="text-sm lg:text-base">Produits</span>
                </a>
                <a href="{{ route('admin.alerts.index') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.alerts.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-bell mr-3"></i>
                    <span class="text-sm lg:text-base">Alertes</span>
                </a>
                <a href="{{ route('admin.subscriptions.index') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.subscriptions.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-credit-card mr-3"></i>
                    <span class="text-sm lg:text-base">Abonnements</span>
                </a>
                <a href="{{ route('admin.price-histories.index') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.price-histories.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-chart-line mr-3"></i>
                    <span class="text-sm lg:text-base">Historique des prix</span>
                </a>
                <a href="{{ route('admin.affiliate-clicks.index') }}" onclick="closeSidebar()" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.affiliate-clicks.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-mouse-pointer mr-3"></i>
                    <span class="text-sm lg:text-base">Clics d'affiliation</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden w-full lg:w-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-4 lg:px-6 py-3 lg:py-4 flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                        <button onclick="toggleSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="text-lg lg:text-xl font-semibold">@yield('page-title', 'Dashboard')</h2>
                    </div>
                    <div class="flex items-center space-x-2 lg:space-x-4">
                        <span class="text-gray-600 text-sm lg:text-base hidden sm:inline">{{ Auth::user()->email ?? 'Admin' }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm lg:text-base px-2 lg:px-0">
                                <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">Déconnexion</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-3 lg:p-3 lg:p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm lg:text-base">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm lg:text-base">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm lg:text-base">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    <script>
        // Fermer le sidebar quand on clique sur l'overlay
        document.getElementById('overlay').addEventListener('click', function() {
            closeSidebar();
        });
        
        // Afficher/masquer l'overlay
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    </script>
</body>
</html>

