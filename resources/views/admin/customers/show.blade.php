@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Customer Details</h1>

    {{-- Customer Info --}}
    <div class="card mb-4">
        <div class="card-header">
            Customer Information (Local DB)
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> {{ $customer->name }}</p>
            <p><strong>Email:</strong> {{ $customer->email }}</p>
            <p><strong>Registered Since:</strong> {{ $customer->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Clerk Info --}}
    @if($clerkUser)
    <div class="card mb-4">
        <div class="card-header">
            Clerk Info
        </div>
        <div class="card-body">
            <p><strong>First Name:</strong> {{ $clerkUser->getFirstName() }}</p>
            <p><strong>Last Name:</strong> {{ $clerkUser->getLastName() }}</p>
            <p><strong>Primary Email:</strong> {{ $clerkUser->getPrimaryEmailAddress() }}</p>
            <p><strong>Clerk User ID:</strong> <code>{{ $clerkUser->getId() }}</code></p>
        </div>
    </div>
    @endif

    {{-- Order History --}}
    <div class="card">
        <div class="card-header">
            Order History
        </div>
        <div class="card-body">
            @if($customer->orders->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td><span class="badge bg-info text-dark">{{ ucfirst($order->status) }}</span></td>
                            <td>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-info">View Order</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>This customer has not placed any orders yet.</p>
            @endif
        </div>
    </div>

    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary mt-3">Back to Customers</a>
</div>
@endsection
