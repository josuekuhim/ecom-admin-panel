@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Add Image to {{ $product->name }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <!-- Image Upload Method Selector -->
            <div class="mb-4">
                <label class="form-label">Choose how to add the image:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="upload_method" id="file_upload" value="file" checked>
                    <label class="form-check-label" for="file_upload">
                        Upload from computer
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="upload_method" id="url_input" value="url">
                    <label class="form-check-label" for="url_input">
                        Use external URL
                    </label>
                </div>
            </div>

            <form action="{{ route('admin.products.images.store', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- File Upload Section -->
                <div id="file_section" class="mb-3">
                    <label for="image" class="form-label">Select Image File</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <div class="form-text">Supported formats: JPEG, PNG, JPG, GIF, SVG, WebP. Max size: 10MB</div>
                </div>

                <!-- URL Input Section -->
                <div id="url_section" class="mb-3" style="display: none;">
                    <label for="image_url" class="form-label">Image URL</label>
                    <input type="url" class="form-control" id="image_url" name="image_url" value="{{ old('image_url') }}" placeholder="https://example.com/image.jpg">
                    <div class="form-text">Enter a direct link to an image file</div>
                </div>

                <div class="mb-3">
                    <label for="alt_text" class="form-label">Alt Text (optional)</label>
                    <input type="text" class="form-control" id="alt_text" name="alt_text" value="{{ old('alt_text') }}" placeholder="Describe the image for accessibility">
                </div>

                <button type="submit" class="btn btn-primary">Add Image</button>
                <a href="{{ route('admin.products.show', $product) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileRadio = document.getElementById('file_upload');
    const urlRadio = document.getElementById('url_input');
    const fileSection = document.getElementById('file_section');
    const urlSection = document.getElementById('url_section');
    const fileInput = document.getElementById('image');
    const urlInput = document.getElementById('image_url');

    function toggleSections() {
        if (fileRadio.checked) {
            fileSection.style.display = 'block';
            urlSection.style.display = 'none';
            fileInput.removeAttribute('disabled');
            urlInput.setAttribute('disabled', 'disabled');
            fileInput.setAttribute('required', 'required');
            urlInput.removeAttribute('required');
            urlInput.value = '';
        } else {
            fileSection.style.display = 'none';
            urlSection.style.display = 'block';
            urlInput.removeAttribute('disabled');
            fileInput.setAttribute('disabled', 'disabled');
            fileInput.removeAttribute('required');
            urlInput.setAttribute('required', 'required');
            fileInput.value = '';
        }
    }

    fileRadio.addEventListener('change', toggleSections);
    urlRadio.addEventListener('change', toggleSections);
    
    // Initialize on page load
    toggleSections();
});
</script>
@endpush
@endsection