@props(['product'])

<div class="p-4">
    <h3 class="font-semibold text-lg mb-2">{{ $product['description'] ?? '' }}</h3>

    <div class="space-y-1 text-sm text-gray-600">
        @if(isset($product['size']) && $product['size'])
            <p><strong>المقاس:</strong> {{ $product['size'] }}</p>
        @endif

        @if(isset($product['unit']) && $product['unit'])
            <p><strong>الوحدة:</strong> {{ $product['unit'] }}</p>
        @endif

        @if(isset($product['price']) && $product['price'])
            <p><strong>السعر:</strong> {{ number_format($product['price'], 2) }} جنيه</p>
        @endif
    </div>
</div>