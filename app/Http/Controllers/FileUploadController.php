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

    /**
     * Opens the native Windows "Open file" dialog on this machine and returns the
     * chosen path. Only works because the browser and server run on the same PC.
     */
    public function browse(Request $request)
    {
        $type = $request->query('type') === 'excel' ? 'excel' : 'pdf';
        $filter = $type === 'pdf'
            ? 'PDF files (*.pdf)|*.pdf'
            : 'Excel files (*.xlsx;*.xls)|*.xlsx;*.xls';
        $title = $type === 'pdf' ? 'Choose the catalog PDF' : 'Choose the price Excel file';
        $initialDir = Session::get('last_browse_dir', config('app.catalog_dir'));

        $script = <<<'PS'
Add-Type -AssemblyName System.Windows.Forms
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$owner = New-Object System.Windows.Forms.Form
$owner.TopMost = $true
$owner.ShowInTaskbar = $false
$owner.Opacity = 0
$owner.StartPosition = 'CenterScreen'
$owner.Show()
$owner.Activate()
$dialog = New-Object System.Windows.Forms.OpenFileDialog
$dialog.Filter = $env:GENPDF_FILTER
$dialog.Title = $env:GENPDF_TITLE
if ($env:GENPDF_DIR -and (Test-Path -LiteralPath $env:GENPDF_DIR)) { $dialog.InitialDirectory = $env:GENPDF_DIR }
$result = $dialog.ShowDialog($owner)
$owner.Close()
if ($result -eq [System.Windows.Forms.DialogResult]::OK) { [Console]::Out.Write($dialog.FileName) }
PS;

        $env = array_merge(getenv(), [
            'GENPDF_FILTER' => $filter,
            'GENPDF_TITLE' => $title,
            'GENPDF_DIR' => (string) $initialDir,
        ]);

        $process = proc_open(
            ['powershell', '-NoProfile', '-STA', '-NonInteractive', '-EncodedCommand', base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'))],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            $env
        );

        if (! is_resource($process)) {
            return response()->json(['success' => false, 'message' => 'Could not open the file dialog.'], 500);
        }

        fclose($pipes[0]);
        $path = trim(stream_get_contents($pipes[1]));
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            Log::warning('File dialog failed', ['exit_code' => $exitCode, 'stderr' => $stderr]);

            return response()->json(['success' => false, 'message' => 'Could not open the file dialog.'], 500);
        }

        return response()->json(['success' => true, 'path' => $path !== '' ? $path : null]);
    }

    public function loadLocal(Request $request)
    {
        $request->validate([
            'pdf_path' => 'required|string',
            'excel_path' => 'required|string',
        ]);

        set_time_limit(0);
        $requestStart = microtime(true);

        try {
            $pdfPath = $this->resolveLocalFile($request->input('pdf_path'), ['pdf'], 'PDF');
            $excelPath = $this->resolveLocalFile($request->input('excel_path'), ['xlsx', 'xls'], 'Excel');

            Session::put('last_browse_dir', dirname($pdfPath));

            Log::info('Local load started', [
                'pdf_path' => $pdfPath,
                'excel_path' => $excelPath,
                'pdf_size' => filesize($pdfPath),
            ]);

            $pages = $this->processFiles($pdfPath, basename($pdfPath), $excelPath, basename($excelPath));

            Log::info('Local load finished', [
                'pages' => $pages,
                'request_seconds' => round(microtime(true) - $requestStart, 2),
            ]);

            return response()->json([
                'success' => true,
                'redirect' => route('products.index'),
            ]);
        } catch (\Exception $e) {
            Log::error('Local load error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function resolveLocalFile($path, array $extensions, $label)
    {
        $path = trim($path, " \t\n\r\0\x0B\"");
        $real = realpath($path);

        if ($real === false || ! is_file($real)) {
            throw new \Exception("{$label} file not found: {$path}");
        }

        if (! in_array(strtolower(pathinfo($real, PATHINFO_EXTENSION)), $extensions, true)) {
            throw new \Exception("{$label} file must be one of: .".implode(', .', $extensions));
        }

        return $real;
    }

    private function processFiles($pdfPath, $pdfName, $excelPath, $excelName)
    {
        if (! FileHelper::hasImagick()) {
            throw new \Exception('Imagick extension is not installed or enabled. Please install the Imagick extension to process PDF files.');
        }

        Session::put('uploaded_excel_path', $excelPath);
        Session::put('uploaded_excel_name', pathinfo($excelName, PATHINFO_FILENAME));
        Session::put('uploaded_pdf_name', pathinfo($pdfName, PATHINFO_FILENAME));

        $result = FileHelper::extractImagesFromPdf($pdfPath, public_path('images'));

        if ($result === false) {
            throw new \Exception('Failed to extract images from PDF. Please ensure the PDF file is not corrupted.');
        }

        return $result;
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

        set_time_limit(0);
        $requestStart = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);

        try {
            Log::info('File upload started', [
                'upload_receive_seconds' => round(microtime(true) - $requestStart, 2),
                'pdf_name' => $request->file('pdf_file')->getClientOriginalName(),
                'excel_name' => $request->file('excel_file')->getClientOriginalName(),
                'pdf_size' => $request->file('pdf_file')->getSize(),
                'excel_size' => $request->file('excel_file')->getSize(),
            ]);

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

            // Only the Excel file is kept; the PDF is read straight from PHP's temp upload
            $excelPath = $uploadsDir.'/uploaded.xlsx';

            $moveStart = microtime(true);
            $excelFile->move($uploadsDir, 'uploaded.xlsx');
            $moveSeconds = round(microtime(true) - $moveStart, 2);

            if (! file_exists($excelPath)) {
                throw new \Exception('Failed to store the Excel file. Please check storage permissions.');
            }

            $extractStart = microtime(true);
            $result = $this->processFiles(
                $pdfFile->getRealPath(),
                $pdfFile->getClientOriginalName(),
                $excelPath,
                $excelFile->getClientOriginalName()
            );

            Log::info('File upload finished', [
                'pages' => $result,
                'move_seconds' => $moveSeconds,
                'extract_seconds' => round(microtime(true) - $extractStart, 2),
                'request_seconds' => round(microtime(true) - $requestStart, 2),
            ]);

            return response()->json([
                'success' => true,
                'redirect' => route('products.index'),
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
        Session::forget('uploaded_excel_path');
        Session::forget('uploaded_excel_name');
        Session::forget('uploaded_pdf_name');
    }
}
