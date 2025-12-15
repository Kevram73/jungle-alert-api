<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Product::with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('asin', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $users = User::orderBy('email')->get();
        return view('admin.products.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amazon_url' => 'required|url',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'current_price' => 'required|numeric|min:0',
            'target_price' => 'nullable|numeric|min:0',
            'asin' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product)
    {
        $product->load('user', 'alerts', 'priceHistories');
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $users = User::orderBy('email')->get();
        return view('admin.products.edit', compact('product', 'users'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amazon_url' => 'required|url',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'current_price' => 'required|numeric|min:0',
            'target_price' => 'nullable|numeric|min:0',
            'asin' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}

