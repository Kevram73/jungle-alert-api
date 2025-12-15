<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateClick;
use Illuminate\Http\Request;

class AffiliateClickController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = AffiliateClick::with(['user', 'product']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('clicked_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('clicked_at', '<=', $request->date_to);
        }

        $clicks = $query->orderBy('clicked_at', 'desc')->paginate(20);

        return view('admin.affiliate-clicks.index', compact('clicks'));
    }

    public function show(AffiliateClick $affiliateClick)
    {
        $affiliateClick->load('user', 'product');
        return view('admin.affiliate-clicks.show', compact('affiliateClick'));
    }
}

