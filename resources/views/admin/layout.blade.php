@php
use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Jungle Alert')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-2xl font-bold">Jungle Alert</h1>
                <p class="text-gray-400 text-sm">Administration</p>
            </div>
            <nav class="mt-8">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-home mr-3"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.users.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-users mr-3"></i>
                    Utilisateurs
                </a>
                <a href="{{ route('admin.products.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.products.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-shopping-bag mr-3"></i>
                    Produits
                </a>
                <a href="{{ route('admin.alerts.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.alerts.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-bell mr-3"></i>
                    Alertes
                </a>
                <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.subscriptions.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-credit-card mr-3"></i>
                    Abonnements
                </a>
                <a href="{{ route('admin.price-histories.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.price-histories.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-chart-line mr-3"></i>
                    Historique des prix
                </a>
                <a href="{{ route('admin.affiliate-clicks.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.affiliate-clicks.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-mouse-pointer mr-3"></i>
                    Clics d'affiliation
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold">@yield('page-title', 'Dashboard')</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">{{ Auth::user()->email ?? 'Admin' }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
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
</body>
</html>

