<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class FileUploadController extends Controller
{
    public function index()
    {
        FileHelper::clearDirectory(public_path('images'));
        $this->cleanupUploadedFiles();

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
                    'message' => "PDF file was rejected by the server. 
                    The file may be too large for the server's upload limit (upload_max_filesize={$uploadMax}, 
                    post_max_size={$postMax}). Uploaded ~{$pdfSize}MB.",
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

            $excelOriginalName = pathinfo($excelFile->getClientOriginalName(), PATHINFO_FILENAME);
            $pdfOriginalName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);

            Session::put('uploaded_pdf_path', $pdfPath);
            Session::put('uploaded_excel_path', $excelPath);
            Session::put('uploaded_excel_name', $excelOriginalName);
            Session::put('uploaded_pdf_name', $pdfOriginalName);

            // Extract images from PDF
            $result = FileHelper::extractImagesFromPdf($pdfPath, public_path('images'));

            if ($result === false) {
                throw new \Exception('Failed to extract images from PDF. Please ensure the PDF file is not corrupted.');
            }

            return response()->json([
                'success' => true,
                'redirect' => route('products.index'),
            ]);

        } catch (\Exception $e) {
            Log::error('File upload error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function processLocalPaths(Request $request)
    {
        $pdfPath = $request->input('pdf_path');
        $excelPath = $request->input('excel_path');

        if (! $pdfPath || ! $excelPath) {
            return response()->json(['success' => false, 'message' => 'Both paths are required'], 422);
        }
        if (! file_exists($pdfPath) || ! is_file($pdfPath)) {
            return response()->json(['success' => false, 'message' => 'PDF file not found: '.$pdfPath], 422);
        }
        if (! file_exists($excelPath) || ! is_file($excelPath)) {
            return response()->json(['success' => false, 'message' => 'Excel file not found: '.$excelPath], 422);
        }
        if (strtolower(pathinfo($pdfPath, PATHINFO_EXTENSION)) !== 'pdf') {
            return response()->json(['success' => false, 'message' => 'The first file must be a PDF'], 422);
        }
        if (! in_array(strtolower(pathinfo($excelPath, PATHINFO_EXTENSION)), ['xlsx', 'xls'])) {
            return response()->json(['success' => false, 'message' => 'The second file must be an Excel file'], 422);
        }

        try {
            if (! FileHelper::hasImagick()) {
                throw new \Exception('Imagick extension is not installed or enabled.');
            }

            FileHelper::clearDirectory(public_path('images'));

            $result = FileHelper::extractImagesFromPdf($pdfPath, public_path('images'));
            if ($result === false) {
                throw new \Exception('Failed to extract images from PDF.');
            }

            Session::put('uploaded_pdf_path', $pdfPath);
            Session::put('uploaded_excel_path', $excelPath);
            Session::put('uploaded_excel_name', pathinfo($excelPath, PATHINFO_FILENAME));
            Session::put('uploaded_pdf_name', pathinfo($pdfPath, PATHINFO_FILENAME));

            return response()->json([
                'success' => true,
                'redirect' => route('products.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Local path processing error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function browse(Request $request)
    {
        $type = $request->query('type', 'pdf');
        $filter = $type === 'pdf'
            ? 'PDF Files (*.pdf)|*.pdf'
            : 'Excel Files (*.xlsx;*.xls)|*.xlsx;*.xls';

        $ps = 'Add-Type -AssemblyName System.Windows.Forms; '
            . '$d = New-Object System.Windows.Forms.OpenFileDialog; '
            . '$d.Filter = \'' . addslashes($filter) . '\'; '
            . 'if ($d.ShowDialog() -eq \'OK\') { $d.FileName }';

        $path = trim((string) shell_exec('powershell -NonInteractive -Command "' . $ps . '"'));

        if ($path === '') {
            return response()->json(['cancelled' => true]);
        }

        return response()->json(['path' => $path]);
    }

    private function cleanupUploadedFiles()
    {
        // Clean up the uploaded files from public/uploads directory
        $uploadsDir = public_path('uploads');

        if (is_dir($uploadsDir)) {
            $files = glob($uploadsDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Also clear session data
        Session::forget('uploaded_pdf_path');
        Session::forget('uploaded_excel_path');
        Session::forget('uploaded_excel_name');
    }
}
