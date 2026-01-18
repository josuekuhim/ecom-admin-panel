@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Cart Details</h1>
        <a href="{{ route('admin.carts.index') }}" class="btn btn-secondary">Back to Carts</a>
    </div>

    {{-- Customer Info --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5>Customer Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> {{ $cart->user->name }}</p>
                    <p><strong>Email:</strong> {{ $cart->user->email }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Cart Created:</strong> {{ $cart->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Last Updated:</strong> {{ $cart->updated_at->diffForHumans() }}</p>
                    @if($cart->updated_at < now()->subHours(24))
                        <p><span class="badge bg-warning">⚠️ Abandoned Cart (24+ hours)</span></p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Cart Items --}}
    <div class="card">
        <div class="card-header">
            <h5>Cart Items ({{ $cart->items->count() }} items, {{ $cart->items->sum('quantity') }} total quantity)</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Stock Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart->items as $item)
                        @php
                            $subtotal = $item->quantity * $item->variant->product->price;
                            $hasStock = $item->variant->stock >= $item->quantity;
                        @endphp
                        <tr class="{{ !$hasStock ? 'table-danger' : '' }}">
                            <td>
                                <a href="{{ route('admin.products.show', $item->variant->product) }}">
                                    {{ $item->variant->product->name }}
                                </a>
                            </td>
                            <td>{{ $item->variant->name }} - {{ $item->variant->value }}</td>
                            <td>R$ {{ number_format($item->variant->product->price, 2, ',', '.') }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
                            <td>
                                @if($hasStock)
                                    <span class="badge bg-success">✓ Available ({{ $item->variant->stock }})</span>
                                @else
                                    <span class="badge bg-danger">⚠️ Insufficient Stock ({{ $item->variant->stock }})</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-info">
                        <th colspan="4">Total</th>
                        <th>R$ {{ number_format($cartTotal, 2, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-3">
        <div class="alert alert-info">
            <h6>Admin Actions:</h6>
            <p class="mb-0">
                • You can view customer details: <a href="{{ route('admin.customers.show', $cart->user) }}" class="btn btn-sm btn-outline-primary">View Customer</a><br>
                • Check product stock levels by clicking on product names above<br>
                • Monitor abandoned carts to improve conversion rates
            </p>
        </div>
    </div>
</div>
@endsection
