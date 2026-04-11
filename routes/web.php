<?php

use App\Http\Controllers\ProductPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/products');
});

Route::get('/products', [ProductPdfController::class, 'index']);
Route::get('/products/download-pdf', [ProductPdfController::class, 'downloadPdf']);
