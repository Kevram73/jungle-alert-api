@extends('admin.layout')

@section('title', 'Alertes')
@section('page-title', 'Alertes')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-3 lg:p-6 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <h3 class="text-base lg:text-lg font-semibold">Liste des alertes</h3>
        <a href="{{ route('admin.alerts.create') }}" class="bg-blue-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-blue-700 text-sm lg:text-base w-full sm:w-auto text-center">
            <i class="fas fa-plus mr-2"></i>Nouvelle alerte
        </a>
    </div>

    <div class="p-3 lg:p-6">
        <form method="GET" action="{{ route('admin.alerts.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2 sm:gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-3 lg:px-4 py-2 flex-1 text-sm lg:text-base">
            <select name="alert_type" class="border rounded px-3 lg:px-4 py-2 text-sm lg:text-base">
                <option value="">Tous les types</option>
                <option value="PRICE_DROP" {{ request('alert_type') == 'PRICE_DROP' ? 'selected' : '' }}>Baisse de prix</option>
                <option value="PRICE_INCREASE" {{ request('alert_type') == 'PRICE_INCREASE' ? 'selected' : '' }}>Augmentation de prix</option>
                <option value="STOCK_AVAILABLE" {{ request('alert_type') == 'STOCK_AVAILABLE' ? 'selected' : '' }}>Stock disponible</option>
            </select>
            <select name="is_active" class="border rounded px-3 lg:px-4 py-2 text-sm lg:text-base">
                <option value="">Tous</option>
                <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Actives</option>
                <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactives</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-gray-700 text-sm lg:text-base">
                <i class="fas fa-search"></i> <span class="hidden sm:inline">Filtrer</span>
            </button>
        </form>

        <!-- Vue mobile (cards) -->
        <div class="block md:hidden space-y-4">
            @forelse($alerts as $alert)
            <div class="bg-gray-50 rounded-lg p-4 border">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <p class="font-semibold text-sm">{{ str_limit($alert->product->title ?? 'N/A', 40) }}</p>
                        <p class="text-xs text-gray-600">{{ $alert->user->email }}</p>
                    </div>
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
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Prix cible:</span>
                        <span class="font-bold">{{ number_format($alert->target_price, 2) }} €</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 text-xs rounded bg-blue-200">{{ $alert->alert_type }}</span>
                        <span class="px-2 py-1 text-xs rounded {{ $alert->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                            {{ $alert->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-gray-500 py-8">Aucune alerte trouvée</div>
            @endforelse
        </div>

        <!-- Vue desktop (tableau) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 text-sm lg:text-base">ID</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Utilisateur</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Produit</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Prix cible</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Type</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Statut</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $alert->id }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $alert->user->email }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ str_limit($alert->product->title ?? 'N/A', 40) }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ number_format($alert->target_price, 2) }} €</td>
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

