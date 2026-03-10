<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTokenController extends Controller
{
    /**
     * List the authenticated user's tokens.
     */
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->displayTokens()->get();

        return response()->json($tokens);
    }

    /**
     * Create a new token for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = $request->user()->createToken($validated['name'], ['read']);

        return response()->json([
            'token' => $token->plainTextToken,
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'abilities' => $token->accessToken->abilities,
        ], 201);
    }

    /**
     * Revoke (delete) a token belonging to the authenticated user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $id)->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        return response()->json(['message' => 'Token revoked.']);
    }
}
