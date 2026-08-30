<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdatePasskeyRequest;
use App\Http\Support\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'password' => $request->password,
            'passkey' => $request->passkey,
            'role' => $request->role ?? User::ROLE_OPERATOR,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success('User registered successfully.', [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Authenticate a user using either password or passkey.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->username)->first();

        if (! $user) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        $verified = false;

        if ($request->filled('password') && Hash::check($request->password, $user->password)) {
            $verified = true;
        } elseif ($request->filled('passkey') && $user->passkey && Hash::check($request->passkey, $user->passkey)) {
            $verified = true;
        }

        if (! $verified) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success('Login successful.', [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Invalidate the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success('Logged out successfully.');
    }

    /**
     * Return the authenticated user's profile.
     */
    public function user(Request $request): JsonResponse
    {
        return ApiResponse::success('Authenticated user retrieved.', $request->user());
    }

    /**
     * Set or update the authenticated user's passkey.
     */
    public function updatePasskey(UpdatePasskeyRequest $request): JsonResponse
    {
        $request->user()->update(['passkey' => $request->passkey]);

        return ApiResponse::success('Passkey updated successfully.', [
            'passkey' => $request->passkey,
        ]);
    }
}
