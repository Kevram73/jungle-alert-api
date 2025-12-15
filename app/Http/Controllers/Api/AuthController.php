<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use PDOException;
use Exception;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|min:3|max:100|unique:users,username',
                'password' => 'required|string|min:8',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            // Database connection or query error
            return response()->json([
                'message' => 'Database connection error. Please check your database configuration.',
                'error' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 503);
        } catch (PDOException $e) {
            // PDO connection error
            return response()->json([
                'message' => 'Database connection error. Please check your database configuration.',
                'error' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 503);
        } catch (Exception $e) {
            // Other errors
            return response()->json([
                'message' => 'An error occurred during validation',
                'error' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 500);
        }

        try {
            $user = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'hashed_password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'subscription_tier' => 'FREE',
                'is_active' => true,
                'is_verified' => false,
                'email_notifications' => true,
                'whatsapp_notifications' => false,
                'push_notifications' => true,
                'gdpr_consent' => false,
                'data_retention_consent' => false,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        } catch (QueryException $e) {
            // Database connection or query error during user creation
            return response()->json([
                'message' => 'Database connection error. Please check your database configuration.',
                'error' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 503);
        } catch (PDOException $e) {
            // PDO connection error during user creation
            return response()->json([
                'message' => 'Database connection error. Please check your database configuration.',
                'error' => config('app.debug') ? $e->getMessage() : 'Service temporarily unavailable',
            ], 503);
        } catch (Exception $e) {
            // Other errors during user creation
            return response()->json([
                'message' => 'Failed to create user',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->hashed_password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }
        
        // Update last login
        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'refresh_token' => $token, // For simplicity
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current user
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}