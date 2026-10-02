<?php

use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ProductPdfController;
use App\Utils\Constants;
use Illuminate\Support\Facades\Route;

Route::prefix(Constants::BASE_URL)->group(function () {
    Route::get('/', [FileUploadController::class, 'index'])->name('landing');
    Route::post('/upload/process', [FileUploadController::class, 'upload'])->name('upload.process');
    Route::get('/browse', [FileUploadController::class, 'browse'])->name('upload.browse');
    Route::post('/load-local', [FileUploadController::class, 'loadLocal'])->name('upload.local');

    Route::get('/products', [ProductPdfController::class, 'index'])->name('products.index');
    Route::get('/products/download-pdf', [ProductPdfController::class, 'downloadPdf'])->name('products.download');
    Route::get('/products/download-and-reset', [ProductPdfController::class, 'downloadAndReset'])->name('products.download-and-reset');
});
