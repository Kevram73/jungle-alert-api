<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Alert::with(['user', 'product']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%");
                })->orWhereHas('product', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.alerts.index', compact('alerts'));
    }

    public function create()
    {
        $users = User::orderBy('email')->get();
        $products = Product::orderBy('title')->get();
        return view('admin.alerts.create', compact('users', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'target_price' => 'required|numeric|min:0',
            'alert_type' => 'required|in:PRICE_DROP,PRICE_INCREASE,STOCK_AVAILABLE',
            'is_active' => 'boolean',
        ]);

        Alert::create($validated);

        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerte créée avec succès.');
    }

    public function show(Alert $alert)
    {
        $alert->load('user', 'product');
        return view('admin.alerts.show', compact('alert'));
    }

    public function edit(Alert $alert)
    {
        $users = User::orderBy('email')->get();
        $products = Product::orderBy('title')->get();
        return view('admin.alerts.edit', compact('alert', 'users', 'products'));
    }

    public function update(Request $request, Alert $alert)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'target_price' => 'required|numeric|min:0',
            'alert_type' => 'required|in:PRICE_DROP,PRICE_INCREASE,STOCK_AVAILABLE',
            'is_active' => 'boolean',
        ]);

        $alert->update($validated);

        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerte mise à jour avec succès.');
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return redirect()->route('admin.alerts.index')
            ->with('success', 'Alerte supprimée avec succès.');
    }
}

