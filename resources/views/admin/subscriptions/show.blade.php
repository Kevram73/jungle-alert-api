@extends('admin.layout')

@section('title', 'Détails abonnement')
@section('page-title', 'Détails abonnement')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6 flex justify-between items-center">
        <h3 class="text-xl font-semibold">Informations abonnement</h3>
        <div class="flex gap-2">
            <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Retour
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600">Utilisateur</label>
            <p class="mt-1">{{ $subscription->user->email }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Plan</label>
            <p class="mt-1">{{ $subscription->plan }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Statut</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded {{ $subscription->status === 'active' ? 'bg-green-200' : ($subscription->status === 'expired' ? 'bg-red-200' : 'bg-yellow-200') }}">
                    {{ $subscription->status }}
                </span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Montant</label>
            <p class="mt-1 text-xl font-bold">{{ number_format($subscription->amount, 2) }} {{ $subscription->currency }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de début</label>
            <p class="mt-1">{{ $subscription->starts_at->format('d/m/Y H:i') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de fin</label>
            <p class="mt-1">{{ $subscription->expires_at->format('d/m/Y H:i') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Référence de paiement</label>
            <p class="mt-1">{{ $subscription->payment_reference ?? 'N/A' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Date de création</label>
            <p class="mt-1">{{ $subscription->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>
@endsection

