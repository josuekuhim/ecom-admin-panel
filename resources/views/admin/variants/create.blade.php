@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Add Variant to {{ $product->name }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.variants.store', $product) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Variant Name (e.g., Size, Color)</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label for="value" class="form-label">Variant Value (e.g., M, Blue)</label>
            <input type="text" class="form-control" id="value" name="value" value="{{ old('value') }}" required>
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="stock" name="stock" value="{{ old('stock', 0) }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Variant</button>
        <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
