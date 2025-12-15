@extends('admin.layout')

@section('title', 'Détails alerte')
@section('page-title', 'Détails alerte')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6 flex justify-between items-center">
        <h3 class="text-xl font-semibold">Informations alerte</h3>
        <div class="flex gap-2">
            <a href="{{ route('admin.alerts.edit', $alert) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <a href="{{ route('admin.alerts.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Retour
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600">Utilisateur</label>
            <p class="mt-1">{{ $alert->user->email }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Produit</label>
            <p class="mt-1">{{ $alert->product->title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Prix cible</label>
            <p class="mt-1 text-xl font-bold">{{ number_format($alert->target_price, 2) }} €</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Type d'alerte</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded bg-blue-200">{{ $alert->alert_type }}</span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Statut</label>
            <p class="mt-1">
                <span class="px-2 py-1 text-xs rounded {{ $alert->is_active ? 'bg-green-200' : 'bg-red-200' }}">
                    {{ $alert->is_active ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Déclenchée le</label>
            <p class="mt-1">{{ $alert->triggered_at ? $alert->triggered_at->format('d/m/Y H:i') : 'Non déclenchée' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600">Notifications</label>
            <div class="mt-1 flex gap-2">
                <span class="px-2 py-1 text-xs rounded {{ $alert->email_sent ? 'bg-green-200' : 'bg-gray-200' }}">Email</span>
                <span class="px-2 py-1 text-xs rounded {{ $alert->whatsapp_sent ? 'bg-green-200' : 'bg-gray-200' }}">WhatsApp</span>
                <span class="px-2 py-1 text-xs rounded {{ $alert->push_sent ? 'bg-green-200' : 'bg-gray-200' }}">Push</span>
            </div>
        </div>
    </div>
</div>
@endsection

