<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Handle Google authentication
     */
    public function handleGoogleAuth(Request $request): JsonResponse
    {
        try {
            Log::info('GoogleAuthController: Received request', [
                'google_id' => $request->input('google_id'),
                'email' => $request->input('email'),
            ]);

            $validated = $request->validate([
                'google_id' => 'required|string',
                'email' => 'required|email',
                'name' => 'required|string',
                'image' => 'nullable|string|url',
            ]);

            Log::info('GoogleAuthController: Validation passed', $validated);

            // Find or create user with Google ID
            $user = User::where('google_id', $validated['google_id'])
                       ->orWhere('email', $validated['email'])
                       ->first();

            if (!$user) {
                Log::info('GoogleAuthController: Creating new user');
                
                // Create new user
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'google_id' => $validated['google_id'],
                    'avatar' => $validated['image'] ?? null,
                    'email_verified_at' => now(),
                    'customer_type' => 'individual',
                    'marketing_emails' => true,
                    'first_login_at' => now(),
                    'last_login_at' => now(),
                    'country' => 'BR',
                ]);

                Log::info('GoogleAuthController: User created', ['user_id' => $user->id]);
                $isNewCustomer = true;
            } else {
                Log::info('GoogleAuthController: Updating existing user', ['user_id' => $user->id]);
                
                // Update existing user with Google data if needed
                $updateData = [];
                
                if (!$user->google_id) {
                    $updateData['google_id'] = $validated['google_id'];
                }
                
                if ($user->name !== $validated['name']) {
                    $updateData['name'] = $validated['name'];
                }

                if ($validated['image'] && $user->avatar !== $validated['image']) {
                    $updateData['avatar'] = $validated['image'];
                }

                if (!empty($updateData)) {
                    $user->update($updateData);
                }

                $isNewCustomer = false;
            }

            // Update last login
            $user->updateLastLogin();

            // Create cart if doesn't exist
            $cart = $user->getOrCreateCart();

            // Generate access token
            $token = $user->createToken('google-auth')->plainTextToken;

            Log::info('GoogleAuthController: Success', [
                'user_id' => $user->id,
                'is_new' => $isNewCustomer,
                'cart_id' => $cart->id
            ]);

            return response()->json([
                'success' => true,
                'message' => $isNewCustomer ? 'Customer created successfully' : 'Customer authenticated',
                'customer' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'avatar' => $user->avatar,
                    'has_complete_profile' => $user->hasCompleteAddress(),
                    'total_orders' => $user->getOrdersCount(),
                    'total_spent' => $user->getTotalOrdersValue(),
                    'cart_items_count' => $cart->items()->count(),
                    'first_login' => $user->first_login_at ? \Carbon\Carbon::parse($user->first_login_at)->diffForHumans() : null,
                    'last_login' => $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : null,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'is_new_customer' => $isNewCustomer,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('GoogleAuthController: Validation error', ['errors' => $e->errors()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('GoogleAuthController: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current customer data
     */
    public function getCurrentCustomer(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Ensure cart exists
            $cart = $user->getOrCreateCart();

            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                    'avatar' => $user->avatar,
                    'has_complete_profile' => $user->hasCompleteAddress(),
                    'total_orders' => $user->getOrdersCount(),
                    'total_spent' => $user->getTotalOrdersValue(),
                    'cart_items_count' => $cart->items()->count(),
                    'first_login' => $user->first_login_at ? \Carbon\Carbon::parse($user->first_login_at)->diffForHumans() : null,
                    'last_login' => $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('GoogleAuthController: Get customer error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get customer data',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }
}
