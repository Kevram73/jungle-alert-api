@extends('admin.layout')

@section('title', 'Abonnements')
@section('page-title', 'Abonnements')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">Liste des abonnements</h3>
        <a href="{{ route('admin.subscriptions.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nouvel abonnement
        </a>
    </div>

    <div class="p-6">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="mb-4 flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-4 py-2 flex-1">
            <select name="status" class="border rounded px-4 py-2">
                <option value="">Tous les statuts</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expirés</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulés</option>
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
                        <th class="text-left py-3 px-4">Utilisateur</th>
                        <th class="text-left py-3 px-4">Plan</th>
                        <th class="text-left py-3 px-4">Statut</th>
                        <th class="text-left py-3 px-4">Montant</th>
                        <th class="text-left py-3 px-4">Début</th>
                        <th class="text-left py-3 px-4">Fin</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $subscription->id }}</td>
                        <td class="py-3 px-4">{{ $subscription->user->email }}</td>
                        <td class="py-3 px-4">{{ $subscription->plan }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded {{ $subscription->status === 'active' ? 'bg-green-200' : ($subscription->status === 'expired' ? 'bg-red-200' : 'bg-yellow-200') }}">
                                {{ $subscription->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4">{{ number_format($subscription->amount, 2) }} {{ $subscription->currency }}</td>
                        <td class="py-3 px-4">{{ $subscription->starts_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-4">{{ $subscription->expires_at->format('d/m/Y') }}</td>
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

