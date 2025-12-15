@extends('admin.layout')

@section('title', 'Créer un produit')
@section('page-title', 'Créer un produit')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('admin.products.store') }}">
        @csrf

        <div class="grid grid-cols-2 gap-6">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2">Utilisateur *</label>
                <select name="user_id" required class="w-full border rounded px-4 py-2">
                    <option value="">Sélectionner un utilisateur</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->email }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2">URL Amazon *</label>
                <input type="url" name="amazon_url" value="{{ old('amazon_url') }}" required class="w-full border rounded px-4 py-2">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2">Titre *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full border rounded px-4 py-2">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="4" class="w-full border rounded px-4 py-2">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">URL Image</label>
                <input type="url" name="image_url" value="{{ old('image_url') }}" class="w-full border rounded px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">ASIN</label>
                <input type="text" name="asin" value="{{ old('asin') }}" class="w-full border rounded px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Prix actuel (€) *</label>
                <input type="number" step="0.01" name="current_price" value="{{ old('current_price') }}" required class="w-full border rounded px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Prix cible (€)</label>
                <input type="number" step="0.01" name="target_price" value="{{ old('target_price') }}" class="w-full border rounded px-4 py-2">
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="mr-2">
                    <span>Actif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Créer
            </button>
            <a href="{{ route('admin.products.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

