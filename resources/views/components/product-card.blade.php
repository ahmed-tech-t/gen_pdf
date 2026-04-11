@props(['product' => [], 'image' => null])

<div class="product-card bg-white rounded-lg shadow-md overflow-hidden">
    <div class="aspect-w-16 aspect-h-9 bg-gray-100">
        @if($image)
            <img src="data:image/jpeg;base64,{{ $image }}" alt="{{ $product['description'] ?? '' }}"
                class="w-full h-48 object-contain">
        @else
            <div class="product-image-placeholder">
                <span>Product Image</span>
            </div>
        @endif
    </div>

    <x-product-details :product="$product" />
</div>