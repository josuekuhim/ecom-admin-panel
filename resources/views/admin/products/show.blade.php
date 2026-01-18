@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>{{ $product->name }}</h1>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Description</h5>
            <p class="card-text">{{ $product->description }}</p>

            <h5 class="card-title">Price</h5>
            <p class="card-text">R$ {{ number_format($product->price, 2, ',', '.') }}</p>

            <h5 class="card-title">Drop</h5>
            <p class="card-text"><a href="{{ route('admin.drops.show', $product->drop) }}">{{ $product->drop->title }}</a></p>
        </div>
    </div>

    {{-- Variants Section --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2>Variants</h2>
            <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-primary">Add Variant</a>
        </div>
        <div class="card-body">
            @if($product->variants->count() > 0)
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Value</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->variants as $variant)
                        <tr>
                            <td>{{ $variant->name }}</td>
                            <td>{{ $variant->value }}</td>
                            <td>{{ $variant->stock }}</td>
                            <td>
                                <a href="{{ route('admin.variants.edit', $variant) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('admin.variants.destroy', $variant) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No variants for this product yet.</p>
            @endif
        </div>
    </div>

    {{-- Images Section removed: images are managed on Edit under Cover Image --}}

    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to Products</a>
</div>
@endsection
