@extends('admin.layout')

@section('title', 'Modifier un abonnement')
@section('page-title', 'Modifier un abonnement')

@section('content')
<div class="bg-white rounded-lg shadow p-3 lg:p-6">
    <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Utilisateur *</label>
                <select name="user_id" required class="w-full border rounded px-3 lg:px-4 py-2">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $subscription->user_id) == $user->id ? 'selected' : '' }}>{{ $user->email }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Plan *</label>
                <input type="text" name="plan" value="{{ old('plan', $subscription->plan) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Statut *</label>
                <select name="status" required class="w-full border rounded px-3 lg:px-4 py-2">
                    <option value="active" {{ old('status', $subscription->status) == 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="expired" {{ old('status', $subscription->status) == 'expired' ? 'selected' : '' }}>Expiré</option>
                    <option value="cancelled" {{ old('status', $subscription->status) == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Montant (€) *</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount', $subscription->amount) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Devise *</label>
                <input type="text" name="currency" value="{{ old('currency', $subscription->currency) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Date de début *</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $subscription->starts_at->format('Y-m-d\TH:i')) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Date de fin *</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $subscription->expires_at->format('Y-m-d\TH:i')) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2">Référence de paiement</label>
                <input type="text" name="payment_reference" value="{{ old('payment_reference', $subscription->payment_reference) }}" class="w-full border rounded px-3 lg:px-4 py-2">
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-4 lg:px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Enregistrer
            </button>
            <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-600 text-white px-4 lg:px-6 py-2 rounded hover:bg-gray-700">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

