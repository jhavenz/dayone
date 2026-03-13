<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\TokenResource;
use App\Models\User;
use DayOne\Contracts\Auth\V1\AuthManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $tokenResult = $this->auth->issueToken($user, 'auth-token');

        return response()->json(
            (new TokenResource($tokenResult, $user))->toArray($request),
            201,
        );
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $tokenResult = $this->auth->issueToken($user, 'auth-token');

        return response()->json(
            (new TokenResource($tokenResult, $user))->toArray($request),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokenId = (string) $user->currentAccessToken()->getKey();

        $this->auth->revokeToken($user, $tokenId);

        return response()->json(['message' => 'Logged out']);
    }
}
