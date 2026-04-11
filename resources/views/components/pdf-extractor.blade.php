@props(['products' => []])

<div class="pdf-extractor">
    <div class="header mb-6">
        <h1 class="text-3xl font-bold mb-4">Product Catalog</h1>
        <a href="/products/download-pdf?t={{ time() }}"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 inline-flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Download PDF
        </a>
    </div>

    <div class="products-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($products as $item)
            <x-product-card :product="$item['data']" :image="$item['image']" />
        @endforeach
    </div>
</div>