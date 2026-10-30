<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /login {username, password}: a Passport access token (a JWT) and the user
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['username'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid username or password'], 401);
        }

        return $this->tokenResponse($user);
    }

    /**
     * POST /refreshtoken: revokes the current token and issues a new one
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->token()->revoke();

        return $this->tokenResponse($user);
    }

    private function tokenResponse(User $user): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('spa')->accessToken,
            'data' => ['userid' => $user->id, 'name' => $user->name, 'role' => $user->role],
        ]);
    }
}
