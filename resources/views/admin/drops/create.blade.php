@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Create Drop</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.drops.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card mb-4">
            <div class="card-header">Drop Details</div>
            <div class="card-body">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="available" name="available" value="1" {{ old('available', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="available">Available</label>
        </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Cover Image</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image" class="form-label">Select Image File</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Create</button>
    </form>
</div>

@endsection
