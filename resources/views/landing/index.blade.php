@extends('app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Upload Product Files</h1>
        
        <form id="uploadForm" action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
            <!-- PDF File Upload -->
            <div>
                <label for="pdf_file" class="block text-sm font-medium text-gray-700 mb-2">
                    PDF File *
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="pdf_file" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload a PDF file</span>
                                <input id="pdf_file" name="pdf_file" type="file" accept=".pdf" class="sr-only" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PDF up to 10MB</p>
                    </div>
                </div>
                <div id="pdf_file_name" class="mt-2 text-sm text-gray-600 hidden"></div>
            </div>

            <!-- Excel File Upload -->
            <div>
                <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2">
                    Excel File *
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M9 12H4v6h5m12-6h-5v6h5M9 24H4v6h5m12-6h-5v6h5M9 36H4v6h5m12-6h-5v6h5M20 4H8a2 2 0 00-2 2v36a2 2 0 002 2h20a2 2 0 002-2V10l-8-6z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload an Excel file</span>
                                <input id="excel_file" name="excel_file" type="file" accept=".xlsx,.xls" class="sr-only" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
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
        const pdfFile = document.getElementById('pdf_file');
        const excelFile = document.getElementById('excel_file');
        const submitBtn = document.getElementById('submitBtn');
        const pdfFileName = document.getElementById('pdf_file_name');
        const excelFileName = document.getElementById('excel_file_name');
        const uploadForm = document.getElementById('uploadForm');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Update file name display and button state
        function updateFormState() {
            const pdfSelected = pdfFile.files.length > 0;
            const excelSelected = excelFile.files.length > 0;
            
            submitBtn.disabled = !(pdfSelected && excelSelected);
            
            if (pdfSelected) {
                pdfFileName.textContent = `Selected: ${pdfFile.files[0].name}`;
                pdfFileName.classList.remove('hidden');
            } else {
                pdfFileName.classList.add('hidden');
            }
            
            if (excelSelected) {
                excelFileName.textContent = `Selected: ${excelFile.files[0].name}`;
                excelFileName.classList.remove('hidden');
            } else {
                excelFileName.classList.add('hidden');
            }
        }

        pdfFile.addEventListener('change', updateFormState);
        excelFile.addEventListener('change', updateFormState);

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadingOverlay.classList.remove('hidden');

            const formData = new FormData(uploadForm);

            fetch('{{ route("upload.process") }}', {
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