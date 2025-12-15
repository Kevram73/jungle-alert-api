@extends('admin.layout')

@section('title', 'Détails clic d\'affiliation')
@section('page-title', 'Détails clic d\'affiliation')

@section('content')
<div class="bg-white rounded-lg shadow p-3 lg:p-6">
    <div class="mb-6">
        <a href="{{ route('admin.affiliate-clicks.index') }}" class="bg-gray-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-gray-700 text-sm lg:text-base text-center">
            Retour
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600">Utilisateur</label>
            <p class="mt-1">{{ $affiliateClick->user->email }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Produit</label>
            <p class="mt-1">{{ $affiliateClick->product->title ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date du clic</label>
            <p class="mt-1">{{ $affiliateClick->clicked_at->format('d/m/Y H:i') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de création</label>
            <p class="mt-1">{{ $affiliateClick->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>
@endsection

