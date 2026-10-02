@extends('app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Upload Product Files</h1>

        @if (session('error'))
            <div class="mb-6 rounded-md bg-red-50 border border-red-200 p-4 text-red-700">
                {{ session('error') }}
            </div>
        @endif
        
        <form id="uploadForm" class="space-y-6">
            @csrf
            <input type="hidden" id="pdf_path" name="pdf_path">
            <input type="hidden" id="excel_path" name="excel_path">
            
            <!-- PDF File -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    PDF File *
                </label>
                <div data-browse="pdf" data-target="pdf_path" data-name="pdf_file_name" role="button" tabindex="0"
                    class="browse-box cursor-pointer mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex justify-center text-sm text-gray-600">
                            <span class="browse-label font-medium text-indigo-600 hover:text-indigo-500">Choose a PDF file</span>
                        </div>
                        <p class="text-xs text-gray-500">PDF files</p>
                    </div>
                </div>
                <div id="pdf_file_name" class="mt-2 text-sm text-gray-600 hidden"></div>
            </div>

            <!-- Excel File -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Excel File *
                </label>
                <div data-browse="excel" data-target="excel_path" data-name="excel_file_name" role="button" tabindex="0"
                    class="browse-box cursor-pointer mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M9 12H4v6h5m12-6h-5v6h5M9 24H4v6h5m12-6h-5v6h5M9 36H4v6h5m12-6h-5v6h5M20 4H8a2 2 0 00-2 2v36a2 2 0 002 2h20a2 2 0 002-2V10l-8-6z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex justify-center text-sm text-gray-600">
                            <span class="browse-label font-medium text-indigo-600 hover:text-indigo-500">Choose an Excel file</span>
                        </div>
                        <p class="text-xs text-gray-500">Excel files only</p>
                    </div>
                </div>
                <div id="excel_file_name" class="mt-2 text-sm text-gray-600 hidden"></div>
            </div>

            <!-- Next Button -->
            <div class="flex justify-center">
                <button type="submit" id="submitBtn" disabled class="disabled:bg-gray-300 disabled:cursor-not-allowed bg-indigo-600 text-white px-6 py-3 rounded-md font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Next
                </button>
            </div>
        </form>
    </div>

    <!-- Loading Spinner (hidden by default) -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600 mx-auto"></div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Processing Files</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Extracting images and processing data. Please wait...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pdfPath = document.getElementById('pdf_path');
        const excelPath = document.getElementById('excel_path');
        const submitBtn = document.getElementById('submitBtn');
        const uploadForm = document.getElementById('uploadForm');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const boxes = document.querySelectorAll('.browse-box');
        let browsing = false;

        // Update button state
        function updateFormState() {
            submitBtn.disabled = !(pdfPath.value && excelPath.value);
        }

        function browse(box) {
            if (browsing) {
                return;
            }
            browsing = true;

            const target = document.getElementById(box.dataset.target);
            const nameDisplay = document.getElementById(box.dataset.name);
            const label = box.querySelector('.browse-label');
            const originalLabel = label.textContent;
            label.textContent = 'Choose in the dialog...';

            fetch('{{ route("upload.browse") }}?type=' + box.dataset.browse, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Could not open the file dialog');
                }
                if (data.path) {
                    target.value = data.path;
                    nameDisplay.textContent = 'Selected: ' + data.path.split(/[\\/]/).pop();
                    nameDisplay.title = data.path;
                    nameDisplay.classList.remove('hidden');
                    updateFormState();
                }
            })
            .catch(error => alert('Error: ' + error.message))
            .finally(() => {
                label.textContent = originalLabel;
                browsing = false;
            });
        }

        boxes.forEach(function(box) {
            box.addEventListener('click', () => browse(box));
            box.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    browse(box);
                }
            });
        });

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadingOverlay.classList.remove('hidden');

            const formData = new FormData(uploadForm);

            fetch('{{ route("upload.local") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                return response.text().then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server error (HTTP ' + response.status + '): ' + text.substring(0, 500));
                    }
                    if (!response.ok) {
                        throw new Error(data.message || 'Upload failed');
                    }
                    return data;
                });
            })
            .then(data => {
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                loadingOverlay.classList.add('hidden');
                alert('Error: ' + error.message);
            });
        });
    });
</script>
@endsection
