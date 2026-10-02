<?php

use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ProductPdfController;
use App\Utils\Constants;
use Illuminate\Support\Facades\Route;

Route::prefix(Constants::BASE_URL)->group(function () {
    Route::get('/', [FileUploadController::class, 'index']);
    Route::post('/upload/process', [FileUploadController::class, 'upload'])->name('upload.process');
    Route::post('/upload/local', [FileUploadController::class, 'processLocalPaths'])->name('upload.local');
    Route::get('/browse', [FileUploadController::class, 'browse'])->name('browse');

    Route::get('/products', [ProductPdfController::class, 'index'])->name('products.index');
    Route::get('/products/download-pdf', [ProductPdfController::class, 'downloadPdf'])->name('products.download');
    Route::get('/products/download-and-reset', [ProductPdfController::class, 'downloadAndReset'])->name('products.download-and-reset');
});
