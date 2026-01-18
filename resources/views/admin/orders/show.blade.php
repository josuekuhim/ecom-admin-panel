@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Order #{{ $order->id }}</h1>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    Order Details
                </div>
                <div class="card-body">
                    <h5 class="card-title">Customer (Local DB)</h5>
                    <p class="card-text">{{ $order->user->name }} ({{ $order->user->email }})</p>

                    <h5 class="card-title">Current Status</h5>
                    <p class="card-text"><span class="badge bg-info text-dark">{{ ucfirst($order->status) }}</span></p>

                    <h5 class="card-title">Order Date</h5>
                    <p class="card-text">{{ $order->created_at->format('d/m/Y H:i') }}</p>

                    <h5 class="card-title">Total Amount</h5>
                    <p class="card-text">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</p>
                </div>
            </div>

            @if($clerkUser)
            <div class="card">
                <div class="card-header">
                    Clerk Customer Info
                </div>
                <div class="card-body">
                    <h5 class="card-title">Name</h5>
                    <p class="card-text">{{ $clerkUser->getFirstName() }} {{ $clerkUser->getLastName() }}</p>

                    <h5 class="card-title">Primary Email</h5>
                    <p class="card-text">{{ $clerkUser->getPrimaryEmailAddress() }}</p>

                    <h5 class="card-title">Clerk User ID</h5>
                    <p class="card-text"><code>{{ $clerkUser->getId() }}</code></p>
                </div>
            </div>
            @endif
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    Update Status
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status</label>
                            <select name="status" id="status" class="form-control">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ $order->status == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            Order Items
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->variant->product->name }}</td>
                        <td>{{ $item->variant->name }} - {{ $item->variant->value }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>R$ {{ number_format($item->price, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary mt-3">Back to Orders</a>
</div>
@endsection
