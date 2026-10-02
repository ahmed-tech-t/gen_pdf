<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Select Product Files</h1>

        <?php if($errors->has('upload')): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <?php echo e($errors->first('upload')); ?>

            </div>
        <?php endif; ?>

        <form id="uploadForm" action="#" method="POST" class="space-y-6">
            <?php echo csrf_field(); ?>

            <!-- PDF File -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">PDF File *</label>
                <div class="flex items-center gap-3">
                    <button type="button" id="pdf-browse-btn" onclick="browseFor('pdf')"
                            class="flex-shrink-0 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Browse…
                    </button>
                    <span id="pdf_label" class="text-sm text-gray-500 truncate">No file selected</span>
                </div>
            </div>

            <!-- Excel File -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Excel File *</label>
                <div class="flex items-center gap-3">
                    <button type="button" id="excel-browse-btn" onclick="browseFor('excel')"
                            class="flex-shrink-0 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Browse…
                    </button>
                    <span id="excel_label" class="text-sm text-gray-500 truncate">No file selected</span>
                </div>
            </div>

            <!-- Next Button -->
            <div class="flex justify-center">
                <button type="submit" id="submitBtn" disabled
                        class="disabled:bg-gray-300 disabled:cursor-not-allowed bg-indigo-600 text-white px-6 py-3 rounded-md font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Next
                </button>
            </div>
        </form>
    </div>

    <!-- Progress Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-6 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold text-gray-900 mb-5 text-center">Processing Files</h3>

            <!-- Steps -->
            <div class="space-y-4">
                <div id="step-extract" class="flex items-center gap-3">
                    <div id="step-extract-icon" class="w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0">
                        <span class="text-gray-400 text-sm">1</span>
                    </div>
                    <div class="flex-1">
                        <p id="step-extract-label" class="text-sm font-medium text-gray-500">Extracting PDF pages</p>
                    </div>
                </div>

                <div id="step-done" class="flex items-center gap-3">
                    <div id="step-done-icon" class="w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0">
                        <span class="text-gray-400 text-sm">2</span>
                    </div>
                    <div class="flex-1">
                        <p id="step-done-label" class="text-sm font-medium text-gray-500">Redirecting</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var pdfPath = null;
    var excelPath = null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    function updateFormState() {
        document.getElementById('submitBtn').disabled = !(pdfPath && excelPath);
    }

    function browseFor(type) {
        var btn = document.getElementById(type + '-browse-btn');
        btn.disabled = true;
        btn.textContent = 'Opening…';

        fetch('<?php echo e(route("browse")); ?>?type=' + type)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.textContent = 'Browse…';
                if (data.cancelled || !data.path) return;
                var parts = data.path.replace(/\\/g, '/').split('/');
                var filename = parts[parts.length - 1];
                if (type === 'pdf') {
                    pdfPath = data.path;
                    document.getElementById('pdf_label').textContent = filename;
                    document.getElementById('pdf_label').className = 'text-sm text-gray-800 font-medium truncate';
                } else {
                    excelPath = data.path;
                    document.getElementById('excel_label').textContent = filename;
                    document.getElementById('excel_label').className = 'text-sm text-gray-800 font-medium truncate';
                }
                updateFormState();
            })
            .catch(function() {
                btn.disabled = false;
                btn.textContent = 'Browse…';
                alert('Could not open file picker. Make sure the server is running.');
            });
    }

    function setStep(id, state) {
        var icon = document.getElementById('step-' + id + '-icon');
        var label = document.getElementById('step-' + id + '-label');
        if (state === 'active') {
            icon.className = 'w-8 h-8 rounded-full border-2 border-indigo-500 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<div class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></div>';
            label.className = 'text-sm font-medium text-indigo-700';
        } else if (state === 'done') {
            icon.className = 'w-8 h-8 rounded-full border-2 border-green-500 bg-green-500 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
            label.className = 'text-sm font-medium text-green-700';
        }
    }

    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!pdfPath || !excelPath) return;

        var loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.remove('hidden');
        setStep('extract', 'active');

        fetch('<?php echo e(route("upload.local")); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ pdf_path: pdfPath, excel_path: excelPath })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                loadingOverlay.classList.add('hidden');
                alert('Error: ' + (data.message || 'Processing failed'));
                return;
            }
            setStep('extract', 'done');
            setStep('done', 'active');
            setTimeout(function() {
                setStep('done', 'done');
                var f = document.createElement('form');
                f.method = 'GET'; f.action = data.redirect;
                document.body.appendChild(f); f.submit();
            }, 500);
        })
        .catch(function() {
            loadingOverlay.classList.add('hidden');
            alert('Network error — could not reach server.');
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH G:\projects\laravel\gen_pdf\gen_pdf\resources\views/landing/index.blade.php ENDPATH**/ ?>