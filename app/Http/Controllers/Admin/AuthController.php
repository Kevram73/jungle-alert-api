<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct()
    {
        // Le middleware guest est appliqué sur les routes
    }

    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->hashed_password)) {
            return back()->withErrors([
                'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
            ])->withInput($request->only('email'));
        }

        // Vérifier si l'utilisateur est actif
        if (!$user->is_active) {
            return back()->withErrors([
                'email' => 'Votre compte est désactivé.',
            ])->withInput($request->only('email'));
        }

        // Connecter l'utilisateur avec la session web
        Auth::login($user, $request->filled('remember'));

        // Mettre à jour la dernière connexion
        $user->update(['last_login' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

