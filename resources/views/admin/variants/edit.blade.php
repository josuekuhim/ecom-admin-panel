@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Edit Variant for {{ $variant->product->name }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.variants.update', $variant) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Variant Name (e.g., Size, Color)</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $variant->name) }}" required>
        </div>
        <div class="mb-3">
            <label for="value" class="form-label">Variant Value (e.g., M, Blue)</label>
            <input type="text" class="form-control" id="value" name="value" value="{{ old('value', $variant->value) }}" required>
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="stock" name="stock" value="{{ old('stock', $variant->stock) }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Variant</button>
        <a href="{{ route('admin.products.show', $variant->product) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
