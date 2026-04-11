<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Utils\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class FileUploadController extends Controller
{
    public function index()
    {
        return view('landing.index');
    }

    public function upload(Request $request)
    {
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');

        if (! $request->hasFile('pdf_file') || ! $request->hasFile('excel_file')) {
            $pdfError = $request->hasFile('pdf_file') ? 'ok' : 'missing';
            $excelError = $request->hasFile('excel_file') ? 'ok' : 'missing';

            if ($pdfError === 'missing' && $request->input('pdf_file') === null) {
                $pdfSize = isset($_SERVER['CONTENT_LENGTH']) ? round($_SERVER['CONTENT_LENGTH'] / 1024 / 1024, 2) : 0;

                return response()->json([
                    'success' => false,
                    'message' => "PDF file was rejected by the server. The file may be too large for the server's upload limit (upload_max_filesize={$uploadMax}, post_max_size={$postMax}). Uploaded ~{$pdfSize}MB.",
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Both PDF and Excel files are required',
            ], 422);
        }

        try {
            Log::info('File upload started', [
                'pdf_name' => $request->file('pdf_file')->getClientOriginalName(),
                'excel_name' => $request->file('excel_file')->getClientOriginalName(),
                'pdf_size' => $request->file('pdf_file')->getSize(),
                'excel_size' => $request->file('excel_file')->getSize(),
            ]);

            // Check if Imagick is available
            if (! FileHelper::hasImagick()) {
                throw new \Exception('Imagick extension is not installed or enabled. Please install the Imagick extension to process PDF files.');
            }

            // Clear images directory
            FileHelper::clearDirectory(public_path('images'));

            // Create uploads directory in public if it doesn't exist
            $uploadsDir = public_path('uploads');
            if (! file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Clear any existing files in uploads directory
            FileHelper::clearDirectory($uploadsDir);

            // Store the uploaded files directly in public directory
            $pdfFile = $request->file('pdf_file');
            $excelFile = $request->file('excel_file');

            // Check if files are valid
            if (! $pdfFile->isValid() || ! $excelFile->isValid()) {
                throw new \Exception('One or more uploaded files are invalid.');
            }

            // Move files to public/uploads directory
            $pdfPath = $uploadsDir.'/uploaded.pdf';
            $excelPath = $uploadsDir.'/uploaded.xlsx';

            $pdfFile->move($uploadsDir, 'uploaded.pdf');
            $excelFile->move($uploadsDir, 'uploaded.xlsx');

            // Verify files were stored successfully
            if (! file_exists($pdfPath) || ! file_exists($excelPath)) {
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
                'redirect' => Constants::BASE_URL.'/products',
                'message' => 'Files uploaded and processed successfully',
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('File upload error: '.$e->getMessage(), [
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
