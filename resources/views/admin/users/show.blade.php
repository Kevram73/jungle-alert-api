@extends('admin.layout')

@section('title', 'Détails utilisateur')
@section('page-title', 'Détails utilisateur')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6 flex justify-between items-center">
        <h3 class="text-xl font-semibold">Informations utilisateur</h3>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Retour
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600">Email</label>
            <p class="mt-1">{{ $user->email }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Nom d'utilisateur</label>
            <p class="mt-1">{{ $user->username }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Prénom</label>
            <p class="mt-1">{{ $user->first_name ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Nom</label>
            <p class="mt-1">{{ $user->last_name ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Type d'abonnement</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded {{ $user->subscription_tier === 'FREE' ? 'bg-gray-200' : ($user->subscription_tier === 'PREMIUM_SIMPLE' ? 'bg-yellow-200' : 'bg-green-200') }}">
                    {{ $user->subscription_tier }}
                </span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Statut</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded {{ $user->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                    {{ $user->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Vérifié</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded {{ $user->is_verified ? 'bg-green-200' : 'bg-red-200' }}">
                    {{ $user->is_verified ? 'Oui' : 'Non' }}
                </span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de création</label>
            <p class="mt-1">{{ $user->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="mt-6">
        <h4 class="font-semibold mb-4">Statistiques</h4>
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-600">Produits</p>
                <p class="text-2xl font-bold">{{ $user->products()->count() }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-600">Alertes</p>
                <p class="text-2xl font-bold">{{ $user->alerts()->count() }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-sm text-gray-600">Abonnements</p>
                <p class="text-2xl font-bold">{{ $user->subscriptions()->count() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

