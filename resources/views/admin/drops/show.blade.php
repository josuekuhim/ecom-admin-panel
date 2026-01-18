@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>{{ $drop->title }}</h1>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Description</h5>
            <p class="card-text">{{ $drop->description }}</p>
        </div>
    </div>

    {{-- Images Section --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2>Images</h2>
            <a href="{{ route('admin.drops.images.create', $drop) }}" class="btn btn-primary">Add Image</a>
        </div>
        <div class="card-body">
            @if($drop->images->count() > 0)
                <div class="row">
                    @foreach($drop->images as $image)
                        <div class="col-md-3">
                            <img src="{{ $image->image_url }}" alt="{{ $image->alt_text }}" class="img-fluid mb-2">
                            <form action="{{ route('admin.drops.images.destroy', [$drop, $image]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p>No images for this drop yet.</p>
            @endif
        </div>
    </div>

    <h2>Products in this Drop</h2>
    @if($drop->products->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($drop->products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->price }}</td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-warning">Edit</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No products in this drop yet.</p>
    @endif

    <a href="{{ route('admin.drops.index') }}" class="btn btn-secondary">Back to Drops</a>
</div>
@endsection
