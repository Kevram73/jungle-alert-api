@extends('admin.layout')

@section('title', 'Produits')
@section('page-title', 'Produits')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-lg font-semibold">Liste des produits</h3>
        <a href="{{ route('admin.products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Nouveau produit
        </a>
    </div>

    <div class="p-6">
        <form method="GET" action="{{ route('admin.products.index') }}" class="mb-4 flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..." class="border rounded px-4 py-2 flex-1">
            <select name="user_id" class="border rounded px-4 py-2">
                <option value="">Tous les utilisateurs</option>
                @foreach(\App\Models\User::orderBy('email')->get() as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->email }}</option>
                @endforeach
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
                        <th class="text-left py-3 px-4">Titre</th>
                        <th class="text-left py-3 px-4">Utilisateur</th>
                        <th class="text-left py-3 px-4">Prix actuel</th>
                        <th class="text-left py-3 px-4">Prix cible</th>
                        <th class="text-left py-3 px-4">Statut</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $product->id }}</td>
                        <td class="py-3 px-4">{{ str_limit($product->title, 50) }}</td>
                        <td class="py-3 px-4">{{ $product->user->email }}</td>
                        <td class="py-3 px-4">{{ number_format($product->current_price, 2) }} €</td>
                        <td class="py-3 px-4">{{ $product->target_price ? number_format($product->target_price, 2) . ' €' : 'N/A' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                                {{ $product->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.products.show', $product) }}" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr ?');">
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
                        <td colspan="7" class="py-8 text-center text-gray-500">Aucun produit trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

