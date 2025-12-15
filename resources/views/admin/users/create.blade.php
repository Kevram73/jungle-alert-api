@extends('admin.layout')

@section('title', 'Créer un utilisateur')
@section('page-title', 'Créer un utilisateur')

@section('content')
<div class="bg-white rounded-lg shadow p-3 lg:p-3 lg:p-6">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Nom d'utilisateur *</label>
                <input type="text" name="username" value="{{ old('username') }}" required class="w-full border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Prénom</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Nom</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Mot de passe *</label>
                <input type="password" name="password" required class="w-full border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Type d'abonnement *</label>
                <select name="subscription_tier" required class="w-full border rounded px-3 lg:px-3 lg:px-4 py-2 text-sm lg:text-base">
                    <option value="FREE" {{ old('subscription_tier') == 'FREE' ? 'selected' : '' }}>Gratuit</option>
                    <option value="PREMIUM_SIMPLE" {{ old('subscription_tier') == 'PREMIUM_SIMPLE' ? 'selected' : '' }}>Premium Simple</option>
                    <option value="PREMIUM_DELUXE" {{ old('subscription_tier') == 'PREMIUM_DELUXE' ? 'selected' : '' }}>Premium Deluxe</option>
                </select>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="mr-2">
                    <span>Actif</span>
                </label>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_verified" value="1" {{ old('is_verified') ? 'checked' : '' }} class="mr-2">
                    <span>Vérifié</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <button type="submit" class="bg-blue-600 text-white px-4 lg:px-4 lg:px-6 py-2 rounded hover:bg-blue-700 text-sm lg:text-base">
                <i class="fas fa-save mr-2"></i>Créer
            </button>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 lg:px-4 lg:px-6 py-2 rounded hover:bg-gray-700 text-center text-sm lg:text-base">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

