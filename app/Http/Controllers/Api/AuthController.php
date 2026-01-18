<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AuthenticateWithClerkAction;
use App\Actions\CompleteProfileAction;
use App\Actions\VerifyClerkSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ClerkAuthRequest;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\CustomerResource;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Handles authentication endpoints.
 *
 * Uses Actions for business logic and CustomerResource for response formatting.
 */
final class AuthController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CompleteProfileAction $completeProfileAction,
        private readonly AuthenticateWithClerkAction $authenticateWithClerk,
        private readonly VerifyClerkSessionAction $verifyClerkSession,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        return $this->tokenResponse($user);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $request->input('email'))->firstOrFail();

        return $this->tokenResponse($user);
    }

    /**
     * Authenticate user via Clerk.
     * Creates customer on first login or updates existing customer.
     */
    public function clerkAuth(ClerkAuthRequest $request): JsonResponse
    {
        $clerkUserId = $request->validated('clerk_user_id');

        Log::info('AuthController: Clerk auth request', [
            'clerk_user_id' => $clerkUserId,
            'ip' => $request->ip(),
        ]);

        $result = $this->authenticateWithClerk->execute($clerkUserId);

        if (!$result) {
            Log::error('AuthController: Clerk auth failed', ['clerk_user_id' => $clerkUserId]);
            return response()->json(['message' => 'Failed to authenticate customer'], 500);
        }

        $customer = $result['customer'];
        $stats = $result['stats'];
        $token = $customer->createToken('clerk_auth_token')->plainTextToken;

        Log::info('AuthController: Clerk auth successful', [
            'customer_id' => $customer->id,
            'clerk_user_id' => $clerkUserId,
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'customer' => (new CustomerResource($customer))->withAuthContext(),
            'stats' => $stats,
        ]);
    }

    /**
     * Verify Clerk session and sync customer.
     */
    public function verifyClerkSession(ClerkAuthRequest $request): JsonResponse
    {
        $clerkUserId = $request->validated('clerk_user_id');

        $customer = $this->verifyClerkSession->execute($clerkUserId);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found or could not be created'], 404);
        }

        return response()->json([
            'customer' => (new CustomerResource($customer))->toArray($request),
        ]);
    }

    /**
     * Complete customer profile with additional information.
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $customer = $request->user();

        if (!$customer) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }

        $success = $this->completeProfileAction->execute($customer, $request->validated());

        if (!$success) {
            return response()->json(['message' => 'Failed to update profile'], 500);
        }

        $customer->refresh();

        return response()->json([
            'message' => 'Profile updated successfully',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'has_complete_profile' => $customer->hasCompleteAddress(),
                'full_address' => $customer->full_address,
            ],
        ]);
    }

    /**
     * Get current customer profile and stats.
     */
    public function getProfile(Request $request): JsonResponse
    {
        $customer = $request->user();

        if (!$customer) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }

        $stats = $this->customerService->getCustomerStats($customer);

        return response()->json([
            'customer' => (new CustomerResource($customer))->withFullProfile(),
            'stats' => $stats,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Generate token response for user.
     */
    private function tokenResponse(User $user): JsonResponse
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
