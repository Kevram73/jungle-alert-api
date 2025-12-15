@extends('admin.layout')

@section('title', 'Modifier un utilisateur')
@section('page-title', 'Modifier un utilisateur')

@section('content')
<div class="bg-white rounded-lg shadow p-3 lg:p-6">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Email *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Nom d'utilisateur *</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Prénom</label>
                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Nom</label>
                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                <input type="password" name="password" class="w-full border rounded px-3 lg:px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Type d'abonnement *</label>
                <select name="subscription_tier" required class="w-full border rounded px-3 lg:px-4 py-2">
                    <option value="FREE" {{ old('subscription_tier', $user->subscription_tier) == 'FREE' ? 'selected' : '' }}>Gratuit</option>
                    <option value="PREMIUM_SIMPLE" {{ old('subscription_tier', $user->subscription_tier) == 'PREMIUM_SIMPLE' ? 'selected' : '' }}>Premium Simple</option>
                    <option value="PREMIUM_DELUXE" {{ old('subscription_tier', $user->subscription_tier) == 'PREMIUM_DELUXE' ? 'selected' : '' }}>Premium Deluxe</option>
                </select>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="mr-2">
                    <span>Actif</span>
                </label>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_verified" value="1" {{ old('is_verified', $user->is_verified) ? 'checked' : '' }} class="mr-2">
                    <span>Vérifié</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-4 lg:px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Enregistrer
            </button>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 lg:px-6 py-2 rounded hover:bg-gray-700">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

