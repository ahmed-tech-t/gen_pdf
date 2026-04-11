<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Utils\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    public function index()
    {
        return view('landing.index');
    }

    public function upload(Request $request)
    {
        // Temporarily disable validation for debugging
        /*
        $validator = Validator::make($request->all(), [
            'pdf_file' => 'required|file|mimes:pdf',
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }
        */

        try {
            // Check if files are present
            if (!$request->hasFile('pdf_file') || !$request->hasFile('excel_file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Both PDF and Excel files are required',
                ], 422);
            }
            // Debug: Log the request
            Log::info('File upload started', [
                'pdf_file' => $request->hasFile('pdf_file'),
                'excel_file' => $request->hasFile('excel_file'),
                'pdf_name' => $request->file('pdf_file') ? $request->file('pdf_file')->getClientOriginalName() : 'none',
                'excel_name' => $request->file('excel_file') ? $request->file('excel_file')->getClientOriginalName() : 'none',
                'pdf_size' => $request->file('pdf_file') ? $request->file('pdf_file')->getSize() : 'none',
                'excel_size' => $request->file('excel_file') ? $request->file('excel_file')->getSize() : 'none',
                'pdf_mime' => $request->file('pdf_file') ? $request->file('pdf_file')->getMimeType() : 'none',
                'excel_mime' => $request->file('excel_file') ? $request->file('excel_file')->getMimeType() : 'none',
            ]);

            // Check if Imagick is available
            if (!FileHelper::hasImagick()) {
                throw new \Exception('Imagick extension is not installed or enabled. Please install the Imagick extension to process PDF files.');
            }

            // Clear images directory
            FileHelper::clearDirectory(public_path('images'));

            // Create uploads directory in public if it doesn't exist
            $uploadsDir = public_path('uploads');
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Clear any existing files in uploads directory
            FileHelper::clearDirectory($uploadsDir);

            // Store the uploaded files directly in public directory
            $pdfFile = $request->file('pdf_file');
            $excelFile = $request->file('excel_file');

            // Check if files are valid
            if (!$pdfFile->isValid() || !$excelFile->isValid()) {
                throw new \Exception('One or more uploaded files are invalid.');
            }

            // Move files to public/uploads directory
            $pdfPath = $uploadsDir . '/uploaded.pdf';
            $excelPath = $uploadsDir . '/uploaded.xlsx';

            $pdfFile->move($uploadsDir, 'uploaded.pdf');
            $excelFile->move($uploadsDir, 'uploaded.xlsx');



            // Verify files were stored successfully
            if (!file_exists($pdfPath) || !file_exists($excelPath)) {
                throw new \Exception('Failed to store uploaded files. Please check storage permissions.');
            }

            // Store file paths in session for ProductPdfController to use
            Session::put('uploaded_pdf_path', $pdfPath);
            Session::put('uploaded_excel_path', $excelPath);

            // Extract images from PDF
            $result = FileHelper::extractImagesFromPdf($pdfPath, public_path('images'));

            if ($result === false) {
                throw new \Exception('Failed to extract images from PDF. Please ensure the PDF file is not corrupted.');
            }

            return response()->json([
                'success' => true,
                'redirect' => Constants::BASE_URL . '/products',
                'message' => 'Files uploaded and processed successfully',
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('File upload error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
