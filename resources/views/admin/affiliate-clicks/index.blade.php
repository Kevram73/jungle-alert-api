@extends('admin.layout')

@section('title', 'Clics d\'affiliation')
@section('page-title', 'Clics d\'affiliation')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <form method="GET" action="{{ route('admin.affiliate-clicks.index') }}" class="mb-4 flex gap-4">
            <select name="user_id" class="border rounded px-4 py-2">
                <option value="">Tous les utilisateurs</option>
                @foreach(\App\Models\User::orderBy('email')->get() as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->email }}</option>
                @endforeach
            </select>
            <select name="product_id" class="border rounded px-4 py-2">
                <option value="">Tous les produits</option>
                @foreach(\App\Models\Product::orderBy('title')->get() as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->title }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Depuis" class="border rounded px-4 py-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Jusqu'à" class="border rounded px-4 py-2">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                <i class="fas fa-search"></i> Filtrer
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4">ID</th>
                        <th class="text-left py-3 px-4">Utilisateur</th>
                        <th class="text-left py-3 px-4">Produit</th>
                        <th class="text-left py-3 px-4">Date du clic</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clicks as $click)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $click->id }}</td>
                        <td class="py-3 px-4">{{ $click->user->email }}</td>
                        <td class="py-3 px-4">{{ str_limit($click->product->title ?? 'N/A', 50) }}</td>
                        <td class="py-3 px-4">{{ $click->clicked_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-4">
                            <a href="{{ route('admin.affiliate-clicks.show', $click) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500">Aucun clic trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $clicks->links() }}
        </div>
    </div>
</div>
@endsection

