@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Active Carts</h1>
        <div>
            <span class="badge bg-info">{{ $carts->total() }} active carts</span>
        </div>
    </div>

    {{-- Search Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.carts.index') }}" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search by customer name or email..." value="{{ $search ?? '' }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table td">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Cart Value</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($carts as $cart)
                        @php
                            $cartValue = $cart->items->sum(function($item) {
                                return $item->quantity * $item->variant->product->price;
                            });
                            $isAbandoned = $cart->updated_at < now()->subHours(24);
                        @endphp
                        <tr class="{{ $isAbandoned ? 'table-warning' : '' }}">
                            <td>
                                <div>
                                    <strong>{{ $cart->user->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $cart->user->email }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $cart->items->count() }} items</span>
                                <span class="badge bg-secondary">{{ $cart->items->sum('quantity') }} qty</span>
                            </td>
                            <td>
                                <strong>R$ {{ number_format($cartValue, 2, ',', '.') }}</strong>
                            </td>
                            <td>
                                {{ $cart->updated_at->diffForHumans() }}
                                @if($isAbandoned)
                                    <br><small class="text-warning">⚠️ Abandoned</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.carts.show', $cart) }}" class="btn btn-sm btn-info">View Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No active carts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $carts->appends(['search' => $search])->links() }}
        </div>
    </div>
</div>
@endsection
