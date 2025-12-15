<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Subscription::with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $users = User::orderBy('email')->get();
        return view('admin.subscriptions.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan' => 'required|string|max:50',
            'status' => 'required|in:active,expired,cancelled',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        Subscription::create($validated);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Abonnement créé avec succès.');
    }

    public function show(Subscription $subscription)
    {
        $subscription->load('user');
        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function edit(Subscription $subscription)
    {
        $users = User::orderBy('email')->get();
        return view('admin.subscriptions.edit', compact('subscription', 'users'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan' => 'required|string|max:50',
            'status' => 'required|in:active,expired,cancelled',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'starts_at' => 'required|date',
            'expires_at' => 'required|date|after:starts_at',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $subscription->update($validated);

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Abonnement mis à jour avec succès.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Abonnement supprimé avec succès.');
    }
}

