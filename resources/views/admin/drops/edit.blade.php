@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Edit Drop</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.drops.update', $drop) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card mb-4">
            <div class="card-header">Drop Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $drop->title) }}" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description', $drop->description) }}</textarea>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="available" name="available" value="1" {{ old('available', $drop->available) ? 'checked' : '' }}>
                    <label class="form-check-label" for="available">Available</label>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Cover Image (optional)</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image" class="form-label">Select Image File</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <div class="form-text">Supported: JPEG, PNG, JPG, GIF, SVG, WebP. Max 10MB</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>

@endsection
