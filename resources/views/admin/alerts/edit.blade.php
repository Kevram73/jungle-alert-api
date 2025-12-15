@extends('admin.layout')

@section('title', 'Modifier une alerte')
@section('page-title', 'Modifier une alerte')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('admin.alerts.update', $alert) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Utilisateur *</label>
                <select name="user_id" required class="w-full border rounded px-4 py-2">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $alert->user_id) == $user->id ? 'selected' : '' }}>{{ $user->email }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Produit *</label>
                <select name="product_id" required class="w-full border rounded px-4 py-2">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id', $alert->product_id) == $product->id ? 'selected' : '' }}>{{ $product->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Prix cible (€) *</label>
                <input type="number" step="0.01" name="target_price" value="{{ old('target_price', $alert->target_price) }}" required class="w-full border rounded px-4 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Type d'alerte *</label>
                <select name="alert_type" required class="w-full border rounded px-4 py-2">
                    <option value="PRICE_DROP" {{ old('alert_type', $alert->alert_type) == 'PRICE_DROP' ? 'selected' : '' }}>Baisse de prix</option>
                    <option value="PRICE_INCREASE" {{ old('alert_type', $alert->alert_type) == 'PRICE_INCREASE' ? 'selected' : '' }}>Augmentation de prix</option>
                    <option value="STOCK_AVAILABLE" {{ old('alert_type', $alert->alert_type) == 'STOCK_AVAILABLE' ? 'selected' : '' }}>Stock disponible</option>
                </select>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $alert->is_active) ? 'checked' : '' }} class="mr-2">
                    <span>Active</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Enregistrer
            </button>
            <a href="{{ route('admin.alerts.index') }}" class="bg-gray-600 text-white px-6 py-2 rounded hover:bg-gray-700">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection

