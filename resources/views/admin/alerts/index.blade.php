@extends('admin.layout')

@section('title', 'Alertes')
@section('page-title', 'Alertes')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">Liste des alertes</h3>
        <a href="{{ route('admin.alerts.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nouvelle alerte
        </a>
    </div>

    <div class="p-6">
        <form method="GET" action="{{ route('admin.alerts.index') }}" class="mb-4 flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-4 py-2 flex-1">
            <select name="alert_type" class="border rounded px-4 py-2">
                <option value="">Tous les types</option>
                <option value="PRICE_DROP" {{ request('alert_type') == 'PRICE_DROP' ? 'selected' : '' }}>Baisse de prix</option>
                <option value="PRICE_INCREASE" {{ request('alert_type') == 'PRICE_INCREASE' ? 'selected' : '' }}>Augmentation de prix</option>
                <option value="STOCK_AVAILABLE" {{ request('alert_type') == 'STOCK_AVAILABLE' ? 'selected' : '' }}>Stock disponible</option>
            </select>
            <select name="is_active" class="border rounded px-4 py-2">
                <option value="">Tous</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actives</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactives</option>
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
                        <th class="text-left py-3 px-4">Produit</th>
                        <th class="text-left py-3 px-4">Prix cible</th>
                        <th class="text-left py-3 px-4">Type</th>
                        <th class="text-left py-3 px-4">Statut</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $alert->id }}</td>
                        <td class="py-3 px-4">{{ $alert->user->email }}</td>
                        <td class="py-3 px-4">{{ str_limit($alert->product->title ?? 'N/A', 40) }}</td>
                        <td class="py-3 px-4">{{ number_format($alert->target_price, 2) }} €</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded bg-blue-200">{{ $alert->alert_type }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded {{ $alert->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                                {{ $alert->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.alerts.show', $alert) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.alerts.edit', $alert) }}" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.alerts.destroy', $alert) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?');">
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
                        <td colspan="7" class="py-8 text-center text-gray-500">Aucune alerte trouvée</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $alerts->links() }}
        </div>
    </div>
</div>
@endsection

