@extends('admin.layout')

@section('title', 'Détails produit')
@section('page-title', 'Détails produit')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6 flex justify-between items-center">
        <h3 class="text-xl font-semibold">Informations produit</h3>
        <div class="flex gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <a href="{{ route('admin.products.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Retour
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600">Titre</label>
            <p class="mt-1">{{ $product->title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Utilisateur</label>
            <p class="mt-1">{{ $product->user->email }}</p>
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-600">Description</label>
            <p class="mt-1">{{ $product->description ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">URL Amazon</label>
            <p class="mt-1"><a href="{{ $product->amazon_url }}" target="_blank" class="text-blue-600 hover:underline">{{ $product->amazon_url }}</a></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">ASIN</label>
            <p class="mt-1">{{ $product->asin ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Prix actuel</label>
            <p class="mt-1 text-xl font-bold">{{ number_format($product->current_price, 2) }} €</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Prix cible</label>
            <p class="mt-1 text-xl font-bold">{{ $product->target_price ? number_format($product->target_price, 2) . ' €' : 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Statut</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                    {{ $product->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de création</label>
            <p class="mt-1">{{ $product->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    @if($product->image_url)
    <div class="mt-6">
        <label class="block text-sm font-medium text-gray-600 mb-2">Image</label>
        <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="max-w-md rounded">
    </div>
    @endif

    <div class="mt-6">
        <h4 class="font-semibold mb-4">Statistiques</h4>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-600">Alertes</p>
                <p class="text-2xl font-bold">{{ $product->alerts->count() }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-600">Historique des prix</p>
                <p class="text-2xl font-bold">{{ $product->priceHistories->count() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

