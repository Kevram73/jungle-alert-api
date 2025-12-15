@extends('admin.layout')

@section('title', 'Abonnements')
@section('page-title', 'Abonnements')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-3 lg:p-6 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="text-base lg:text-lg font-semibold">Liste des abonnements</h3>
        <a href="{{ route('admin.subscriptions.create') }}" class="bg-blue-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-blue-700 text-sm lg:text-base w-full sm:w-auto text-center">
            <i class="fas fa-plus mr-2"></i>Nouvel abonnement
        </a>
    </div>

    <div class="p-3 lg:p-6">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2 sm:gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-3 lg:px-4 py-2 flex-1 text-sm lg:text-base">
            <select name="status" class="border rounded px-3 lg:px-4 py-2 text-sm lg:text-base">
                <option value="">Tous les statuts</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expirés</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulés</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-gray-700 text-sm lg:text-base">
                <i class="fas fa-search"></i> <span class="hidden sm:inline">Filtrer</span>
            </button>
        </form>

        <!-- Vue mobile (cards) -->
        <div class="block md:hidden space-y-4">
            @forelse($subscriptions as $subscription)
            <div class="bg-gray-50 rounded-lg p-4 border">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <p class="font-semibold text-sm">{{ $subscription->user->email }}</p>
                        <p class="text-xs text-gray-600">{{ $subscription->plan }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.subscriptions.destroy', $subscription) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Montant:</span>
                        <span class="font-bold">{{ number_format($subscription->amount, 2) }} {{ $subscription->currency }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Début:</span>
                        <span>{{ $subscription->starts_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Fin:</span>
                        <span>{{ $subscription->expires_at->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="px-2 py-1 text-xs rounded {{ $subscription->status === 'active' ? 'bg-green-200' : ($subscription->status === 'expired' ? 'bg-red-200' : 'bg-yellow-200') }}">
                            {{ $subscription->status }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-gray-500 py-8">Aucun abonnement trouvé</div>
            @endforelse
        </div>

        <!-- Vue desktop (tableau) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 text-sm lg:text-base">ID</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Utilisateur</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Plan</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Statut</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Montant</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Début</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Fin</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $subscription->id }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $subscription->user->email }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $subscription->plan }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded {{ $subscription->status === 'active' ? 'bg-green-200' : ($subscription->status === 'expired' ? 'bg-red-200' : 'bg-yellow-200') }}">
                                {{ $subscription->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ number_format($subscription->amount, 2) }} {{ $subscription->currency }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $subscription->starts_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $subscription->expires_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.subscriptions.destroy', $subscription) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?');">
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
                        <td colspan="8" class="py-8 text-center text-gray-500">Aucun abonnement trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $subscriptions->links() }}
        </div>
    </div>
</div>
@endsection

