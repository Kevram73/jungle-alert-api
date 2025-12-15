@extends('admin.layout')

@section('title', 'Utilisateurs')
@section('page-title', 'Utilisateurs')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">Liste des utilisateurs</h3>
        <a href="{{ route('admin.users.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nouvel utilisateur
        </a>
    </div>

    <div class="p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-4 py-2 flex-1">
            <select name="subscription_tier" class="border rounded px-4 py-2">
                <option value="">Tous les abonnements</option>
                <option value="FREE" {{ request('subscription_tier') == 'FREE' ? 'selected' : '' }}>Gratuit</option>
                <option value="PREMIUM_SIMPLE" {{ request('subscription_tier') == 'PREMIUM_SIMPLE' ? 'selected' : '' }}>Premium Simple</option>
                <option value="PREMIUM_DELUXE" {{ request('subscription_tier') == 'PREMIUM_DELUXE' ? 'selected' : '' }}>Premium Deluxe</option>
            </select>
            <select name="is_active" class="border rounded px-4 py-2">
                <option value="">Tous</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actifs</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactifs</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                <i class="fas fa-search"></i> Filtrer
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4">ID</th>
                        <th class="text-left py-3 px-4">Email</th>
                        <th class="text-left py-3 px-4">Nom</th>
                        <th class="text-left py-3 px-4">Abonnement</th>
                        <th class="text-left py-3 px-4">Statut</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $user->id }}</td>
                        <td class="py-3 px-4">{{ $user->email }}</td>
                        <td class="py-3 px-4">{{ $user->first_name }} {{ $user->last_name }}</td>
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

