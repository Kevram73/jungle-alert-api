@extends('admin.layout')

@section('title', 'Historique des prix')
@section('page-title', 'Historique des prix')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <form method="GET" action="{{ route('admin.price-histories.index') }}" class="mb-4 flex gap-4">
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
                        <th class="text-left py-3 px-4">Produit</th>
                        <th class="text-left py-3 px-4">Utilisateur</th>
                        <th class="text-left py-3 px-4">Prix</th>
                        <th class="text-left py-3 px-4">Date</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priceHistories as $priceHistory)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $priceHistory->id }}</td>
                        <td class="py-3 px-4">{{ str_limit($priceHistory->product->title ?? 'N/A', 50) }}</td>
                        <td class="py-3 px-4">{{ $priceHistory->product->user->email ?? 'N/A' }}</td>
                        <td class="py-3 px-4 font-bold">{{ number_format($priceHistory->price, 2) }} €</td>
                        <td class="py-3 px-4">{{ $priceHistory->recorded_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3 px-4">
                            <a href="{{ route('admin.price-histories.show', $priceHistory) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-gray-500">Aucun historique trouvé</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $priceHistories->links() }}
        </div>
    </div>
</div>
@endsection

