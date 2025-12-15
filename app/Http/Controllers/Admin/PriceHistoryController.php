<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\Request;

class PriceHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = PriceHistory::with('product.user');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('recorded_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('recorded_at', '<=', $request->date_to);
        }

        $priceHistories = $query->orderBy('recorded_at', 'desc')->paginate(20);

        return view('admin.price-histories.index', compact('priceHistories'));
    }

    public function show(PriceHistory $priceHistory)
    {
        $priceHistory->load('product.user');
        return view('admin.price-histories.show', compact('priceHistory'));
    }
}

