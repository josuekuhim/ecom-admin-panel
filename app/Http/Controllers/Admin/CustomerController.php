<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ClerkClient;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = User::with(['cart.items', 'orders'])
            ->latest()
            ->paginate(15);
            
        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $customer, ClerkClient $clerkService)
    {
        $customer->load('orders.items'); // Eager load orders and their items

        $clerkUser = null;
        if ($customer->clerk_user_id) {
            $clerkUser = $clerkService->getUser($customer->clerk_user_id);
        }

        return view('admin.customers.show', compact('customer', 'clerkUser'));
    }
}
