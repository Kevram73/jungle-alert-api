@extends('admin.layout')

@section('title', 'Détails historique des prix')
@section('page-title', 'Détails historique des prix')

@section('content')
<div class="bg-white rounded-lg shadow p-3 lg:p-6">
    <div class="mb-6">
        <a href="{{ route('admin.price-histories.index') }}" class="bg-gray-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-gray-700 text-sm lg:text-base text-center">
            Retour
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600">Produit</label>
            <p class="mt-1">{{ $priceHistory->product->title ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Utilisateur</label>
            <p class="mt-1">{{ $priceHistory->product->user->email ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Prix</label>
            <p class="mt-1 text-xl font-bold">{{ number_format($priceHistory->price, 2) }} €</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date d'enregistrement</label>
            <p class="mt-1">{{ $priceHistory->recorded_at->format('d/m/Y H:i') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de création</label>
            <p class="mt-1">{{ $priceHistory->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>
@endsection

