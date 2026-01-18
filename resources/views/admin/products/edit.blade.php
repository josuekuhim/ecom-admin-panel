@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Edit Product</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        {{-- Product Details Card --}}
        <div class="card mb-4">
            <div class="card-header">Product Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description', $product->description) }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Base Price</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" value="{{ old('price', $product->price) }}" required>
                </div>
                <div class="mb-3">
                    <label for="drop_id" class="form-label">Drop</label>
                    <select class="form-control" id="drop_id" name="drop_id" required>
                        @foreach($drops as $drop)
                            <option value="{{ $drop->id }}" {{ old('drop_id', $product->drop_id) == $drop->id ? 'selected' : '' }}>
                                {{ $drop->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="available" name="available" value="1" {{ old('available', $product->available) ? 'checked' : '' }}>
                    <label class="form-check-label" for="available">Available</label>
                </div>
            </div>
        </div>

        {{-- Product Images (multiple upload support) --}}
        <div class="card mb-4">
            <div class="card-header">Imagens do Produto</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="images" class="form-label">Adicionar Novas Imagens (múltiplas)</label>
                    <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                    <div class="form-text">Selecione uma ou múltiplas imagens. Suporta: JPEG, PNG, JPG, GIF, SVG, WebP. Máx 10MB cada</div>
                </div>
                <div class="mb-3">
                    <label for="alt_text" class="form-label">Texto Alt (opcional - aplicado à primeira imagem)</label>
                    <input type="text" class="form-control" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" placeholder="Descreva a imagem">
                </div>

                {{-- Existing product images (manage/delete) --}}
                <hr>
                <h6 class="mb-3">Imagens atuais ({{ $product->images ? $product->images->count() : 0 }})</h6>
                @if($product->images && $product->images->count() > 0)
                    <div class="row">
                        @foreach($product->images as $image)
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-2 text-center">
                                    <img src="{{ $image->image_url }}" alt="{{ $image->alt_text }}" class="img-fluid mb-2" style="max-height: 150px; object-fit: cover;" />
                                    <small class="d-block text-muted mb-2">{{ $image->original_filename }}</small>
                                    <form action="{{ route('admin.products.images.destroy', [$product, $image]) }}" method="POST" onsubmit="return confirm('Excluir esta imagem?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-100">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">Nenhuma imagem enviada ainda. Use o campo acima para adicionar.</p>
                @endif
            </div>
        </div>

        {{-- Variants Card --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Variants</span>
                <button type="button" id="add-variant-btn" class="btn btn-sm btn-success">Add New Variant</button>
            </div>
            <div class="card-body">
                <div id="variants-container">
                    {{-- Existing variants --}}
                    @foreach ($product->variants as $index => $variant)
                        <div class="row mb-3 align-items-end variant-row">
                             <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant->id }}">
                            <div class="col-md-4">
                                <label class="form-label">Name</label>
                                <input type="text" name="variants[{{ $index }}][name]" class="form-control" value="{{ $variant->name }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Value</label>
                                <input type="text" name="variants[{{ $index }}][value]" class="form-control" value="{{ $variant->value }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="variants[{{ $index }}][stock]" class="form-control" value="{{ $variant->stock }}" required min="0">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger remove-variant-btn">X</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Product</button>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('variants-container');
    const addBtn = document.getElementById('add-variant-btn');
    // Start index from the number of existing variants to avoid key conflicts
    let variantIndex = {{ $product->variants->count() }};

    addBtn.addEventListener('click', function () {
        const variantRow = document.createElement('div');
        variantRow.classList.add('row', 'mb-3', 'align-items-end', 'variant-row');
        // Note the empty 'id' field for new variants
        variantRow.innerHTML = `
            <input type="hidden" name="variants[${variantIndex}][id]" value="">
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="variants[${variantIndex}][name]" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Value</label>
                <input type="text" name="variants[${variantIndex}][value]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock</label>
                <input type="number" name="variants[${variantIndex}][stock]" class="form-control" required min="0">
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
            e.target.closest('.variant-row').remove();
        }
    });
});
</script>
@endpush
@endsection
