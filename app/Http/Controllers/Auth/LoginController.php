<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Authenticate user via API and return Sanctum token with Spatie roles.
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Find User & Verify Credentials
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // 3. Optional: Check if user is verified (based on your previous code)
        // Ensure 'is_verified' exists in your users table migration
        if (isset($user->is_verified) && !$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not verified.',
            ], 403);
        }

        // 4. Generate Sanctum Token
        // Requires 'use Laravel\Sanctum\HasApiTokens;' in User Model
        $token = $user->createToken('api-access-token')->plainTextToken;

        // 5. Return Response with Spatie Roles/Permissions
        // 1. Eager load roles and permissions before returning
        $user->load(['roles', 'permissions']);
        $user->all_permissions = $user->getAllPermissions()->pluck('name');
        $user->role_names = $user->getRoleNames();
        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => $user // This returns all model fields + roles + permissions
            ]
        ], 200);

    }

    /**
     * Log out the user (Revoke the token).
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }
}
