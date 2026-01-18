@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Orders</h1>

    {{-- Filter Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.orders.index') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search Customer</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search by name or email..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-md-5">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" {{ ($status ?? '') == $s ? 'selected' : '' }}>
                                    {{ ucfirst($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table td">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusColors = [
                            'pending' => 'warning',
                            'paid' => 'success',
                            'processing' => 'primary',
                            'shipped' => 'info',
                            'delivered' => 'secondary',
                            'canceled' => 'danger',
                        ];
                    @endphp
                    @forelse($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>
                            <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td>R$ {{ number_format($order->total_amount, 2, ',', '.') }}</td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Append search and status query to pagination links --}}
            {{ $orders->appends(['search' => $search, 'status' => $status])->links() }}
        </div>
    </div>
</div>
@endsection
