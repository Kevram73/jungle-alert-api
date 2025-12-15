@extends('admin.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Total Utilisateurs</p>
                <p class="text-2xl font-bold">{{ \App\Models\User::count() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-shopping-bag text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Total Produits</p>
                <p class="text-2xl font-bold">{{ \App\Models\Product::count() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-bell text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Alertes Actives</p>
                <p class="text-2xl font-bold">{{ \App\Models\Alert::where('is_active', true)->count() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-credit-card text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Abonnements Actifs</p>
                <p class="text-2xl font-bold">{{ \App\Models\Subscription::where('status', 'active')->count() }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Derniers utilisateurs</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Email</th>
                        <th class="text-left py-2">Abonnement</th>
                        <th class="text-left py-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\User::latest()->take(5)->get() as $user)
                    <tr class="border-b">
                        <td class="py-2">{{ $user->email }}</td>
                        <td class="py-2">
                            <span class="px-2 py-1 text-xs rounded {{ $user->subscription_tier === 'FREE' ? 'bg-gray-200' : ($user->subscription_tier === 'PREMIUM_SIMPLE' ? 'bg-yellow-200' : 'bg-green-200') }}">
                                {{ $user->subscription_tier }}
                            </span>
                        </td>
                        <td class="py-2 text-sm text-gray-600">{{ $user->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Alertes récentes</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Produit</th>
                        <th class="text-left py-2">Type</th>
                        <th class="text-left py-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\Alert::with('product')->latest()->take(5)->get() as $alert)
                    <tr class="border-b">
                        <td class="py-2">{{ str_limit($alert->product->title ?? 'N/A', 30) }}</td>
                        <td class="py-2">
                            <span class="px-2 py-1 text-xs rounded bg-blue-200">
                                {{ $alert->alert_type }}
                            </span>
                        </td>
                        <td class="py-2 text-sm text-gray-600">{{ $alert->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

