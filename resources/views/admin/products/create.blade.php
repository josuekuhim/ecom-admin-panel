@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Create Product</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        {{-- Product Details Card --}}
        <div class="card mb-4">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Base Price</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" value="{{ old('price') }}" required>
                </div>
                <div class="mb-3">
                    <label for="drop_id" class="form-label">Drop</label>
                    <select class="form-control" id="drop_id" name="drop_id" required>
                        <option value="">Select a Drop</option>
                        @foreach($drops as $drop)
                            <option value="{{ $drop->id }}" {{ old('drop_id') == $drop->id ? 'selected' : '' }}>
                                {{ $drop->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="available" name="available" value="1" {{ old('available', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="available">Available</label>
                </div>
            </div>
        </div>

        {{-- Cover Image (optional) --}}
        <div class="card mb-4">
            <div class="card-header">Cover Image (optional)</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image" class="form-label">Select Image File</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>
                <div class="mb-3">
                    <label for="alt_text" class="form-label">Alt Text (optional)</label>
                    <input type="text" class="form-control" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" placeholder="Describe the image">
                </div>
            </div>
        </div>

        {{-- Variants Card --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Variants</span>
                <button type="button" id="add-variant-btn" class="btn btn-sm btn-success">Add Variant</button>
            </div>
            <div class="card-body">
                <div id="variants-container">
                    {{-- Dynamic variant fields will be added here --}}
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Create Product</button>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('variants-container');
    const addBtn = document.getElementById('add-variant-btn');
    let variantIndex = 0;

    addBtn.addEventListener('click', function () {
        const variantRow = document.createElement('div');
        variantRow.classList.add('row', 'mb-3', 'align-items-end');
        variantRow.innerHTML = `
            <div class="col-md-4">
                <label for="variant_name_${variantIndex}" class="form-label">Name (e.g., Size)</label>
                <input type="text" name="variants[${variantIndex}][name]" id="variant_name_${variantIndex}" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="variant_value_${variantIndex}" class="form-label">Value (e.g., M)</label>
                <input type="text" name="variants[${variantIndex}][value]" id="variant_value_${variantIndex}" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="variant_stock_${variantIndex}" class="form-label">Stock</label>
                <input type="number" name="variants[${variantIndex}][stock]" id="variant_stock_${variantIndex}" class="form-control" required min="0">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger remove-variant-btn">X</button>
            </div>
        `;
        container.appendChild(variantRow);
        variantIndex++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-variant-btn')) {
            e.target.closest('.row').remove();
        }
    });
});
</script>
@endpush
@endsection
