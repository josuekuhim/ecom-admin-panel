@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Customers</h1>

    <div class="card">
        <div class="card-body">
            <table class="table td">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Cart Items</th>
                        <th>Orders</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>{{ $customer->id }}</td>
                        <td>
                            {{ $customer->name }}
                            @if($customer->clerk_user_id)
                                <span class="badge bg-primary ms-1">Clerk</span>
                            @endif
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->customer_type ?? 'individual' }}</td>
                        <td>
                            @if($customer->cart && $customer->cart->items->count() > 0)
                                <span class="badge bg-success">{{ $customer->cart->items->count() }} items</span>
                            @else
                                <span class="badge bg-secondary">Empty</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $customer->orders->count() }}</span>
                        </td>
                        <td>{{ $customer->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-info">View Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">No customers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection
