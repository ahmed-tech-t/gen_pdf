@props(['products' => []])

<div class="pdf-extractor">
    <div class="header mb-6">
        <h1 class="text-3xl font-bold mb-4">Product Catalog</h1>
        <button id="downloadPdfBtn" 
                class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 inline-flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Download PDF and Return to Upload
        </button>
    </div>

    <div class="products-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($products as $item)
            <x-product-card :product="$item['data']" :image="$item['image']" />
        @endforeach
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const downloadBtn = document.getElementById('downloadPdfBtn');
    
    downloadBtn.addEventListener('click', function() {
        // Show loading state
        const originalText = downloadBtn.innerHTML;
        downloadBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...';
        downloadBtn.disabled = true;
        
        // Trigger the download
        window.location.href = '{{ route("products.download-and-reset") }}';
        
        // After a short delay, redirect to landing page
        setTimeout(function() {
            window.location.href = '/';
        }, 2000);
    });
});
</script>