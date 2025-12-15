@extends('admin.layout')

@section('title', 'Utilisateurs')
@section('page-title', 'Utilisateurs')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-3 lg:p-3 lg:p-6 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="text-base lg:text-lg font-semibold">Liste des utilisateurs</h3>
        <a href="{{ route('admin.users.create') }}" class="bg-blue-600 text-white px-3 lg:px-3 lg:px-4 py-2 rounded hover:bg-blue-700 text-sm lg:text-base w-full sm:w-auto text-center">
            <i class="fas fa-plus mr-2"></i>Nouvel utilisateur
        </a>
    </div>

    <div class="p-3 lg:p-3 lg:p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2 sm:gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-3 lg:px-3 lg:px-4 py-2 flex-1 text-sm lg:text-base">
            <select name="subscription_tier" class="border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
                <option value="">Tous les abonnements</option>
                <option value="FREE" {{ request('subscription_tier') == 'FREE' ? 'selected' : '' }}>Gratuit</option>
                <option value="PREMIUM_SIMPLE" {{ request('subscription_tier') == 'PREMIUM_SIMPLE' ? 'selected' : '' }}>Premium Simple</option>
                <option value="PREMIUM_DELUXE" {{ request('subscription_tier') == 'PREMIUM_DELUXE' ? 'selected' : '' }}>Premium Deluxe</option>
            </select>
            <select name="is_active" class="border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
                <option value="">Tous</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actifs</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactifs</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-3 lg:px-3 lg:px-4 py-2 rounded hover:bg-gray-700 text-sm lg:text-base">
                <i class="fas fa-search"></i> <span class="hidden sm:inline">Filtrer</span>
            </button>
        </form>

        <!-- Vue mobile (cards) -->
        <div class="block md:hidden space-y-4">
            @forelse($users as $user)
            <div class="bg-gray-50 rounded-lg p-4 border">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <p class="font-semibold text-sm">{{ $user->email }}</p>
                        <p class="text-xs text-gray-600">{{ $user->first_name }} {{ $user->last_name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 text-xs rounded {{ $user->subscription_tier === 'FREE' ? 'bg-gray-200' : ($user->subscription_tier === 'PREMIUM_SIMPLE' ? 'bg-yellow-200' : 'bg-green-200') }}">
                        {{ $user->subscription_tier }}
                    </span>
                    <span class="px-2 py-1 text-xs rounded {{ $user->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="text-center text-gray-500 py-8">Aucun utilisateur trouvé</div>
            @endforelse
        </div>

        <!-- Vue desktop (tableau) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 text-sm lg:text-base">ID</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Email</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Nom</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Abonnement</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Statut</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $user->id }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $user->email }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded {{ $user->subscription_tier === 'FREE' ? 'bg-gray-200' : ($user->subscription_tier === 'PREMIUM_SIMPLE' ? 'bg-yellow-200' : 'bg-green-200') }}">
                                {{ $user->subscription_tier }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded {{ $user->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">Aucun utilisateur trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

