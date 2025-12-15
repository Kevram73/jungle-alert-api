@extends('admin.layout')

@section('title', 'Historique des prix')
@section('page-title', 'Historique des prix')

@section('content')
<div class="bg-white rounded-lg shadow">
    <div class="p-3 lg:p-6">
        <form method="GET" action="{{ route('admin.price-histories.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2 sm:gap-4">
            <select name="product_id" class="border rounded px-3 lg:px-4 py-2 text-sm lg:text-base">
                <option value="">Tous les produits</option>
                @foreach(\App\Models\Product::orderBy('title')->get() as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->title }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="Depuis" class="border rounded px-3 lg:px-4 py-2 text-sm lg:text-base">
            <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="Jusqu'à" class="border rounded px-3 lg:px-4 py-2 text-sm lg:text-base">
            <button type="submit" class="bg-gray-600 text-white px-3 lg:px-4 py-2 rounded hover:bg-gray-700 text-sm lg:text-base">
                <i class="fas fa-search"></i> <span class="hidden sm:inline">Filtrer</span>
            </button>
        </form>

        <!-- Vue mobile (cards) -->
        <div class="block md:hidden space-y-4">
            @forelse($priceHistories as $priceHistory)
            <div class="bg-gray-50 rounded-lg p-4 border">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex-1">
                        <p class="font-semibold text-sm">{{ str_limit($priceHistory->product->title ?? 'N/A', 40) }}</p>
                        <p class="text-xs text-gray-600">{{ $priceHistory->product->user->email ?? 'N/A' }}</p>
                    </div>
                    <a href="{{ route('admin.price-histories.show', $priceHistory) }}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Prix:</span>
                        <span class="font-bold text-lg">{{ number_format($priceHistory->price, 2) }} €</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Date:</span>
                        <span>{{ $priceHistory->recorded_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-gray-500 py-8">Aucun historique trouvé</div>
            @endforelse
        </div>

        <!-- Vue desktop (tableau) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-left py-3 px-4 text-sm lg:text-base">ID</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Produit</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Utilisateur</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Prix</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Date</th>
                        <th class="text-left py-3 px-4 text-sm lg:text-base">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priceHistories as $priceHistory)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $priceHistory->id }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ str_limit($priceHistory->product->title ?? 'N/A', 50) }}</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $priceHistory->product->user->email ?? 'N/A' }}</td>
                        <td class="py-3 px-4 font-bold text-sm lg:text-base">{{ number_format($priceHistory->price, 2) }} €</td>
                        <td class="py-3 px-4 text-sm lg:text-base">{{ $priceHistory->recorded_at->format('d/m/Y H:i') }}</td>
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

