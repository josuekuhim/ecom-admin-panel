<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ClerkClient;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'canceled'];

        $orders = Order::with('user')
            ->when($search, function ($query, $search) {
                // Search by customer name or email
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                // Filter by status
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return view('admin.orders.index', compact('orders', 'statuses', 'search', 'status'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order, ClerkClient $clerkService)
    {
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'canceled'];

        $clerkUser = null;
        if ($order->user && $order->user->clerk_user_id) {
            $clerkUser = $clerkService->getUser($order->user->clerk_user_id);
        }

        return view('admin.orders.show', compact('order', 'statuses', 'clerkUser'));
    }

    /**
     * Update the order's status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $statuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'canceled'];

        $request->validate([
            'status' => ['required', Rule::in($statuses)],
        ]);

        $order->status = $request->status;
        $order->save();

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated successfully.');
    }
}
