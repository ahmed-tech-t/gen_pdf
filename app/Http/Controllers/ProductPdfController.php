<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Services\PdfImageExtractor;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductPdfController extends Controller
{
    protected $excelPath;

    protected $pdfPath;

    protected $pdfImageExtractor;

    public function __construct()
    {
        // Use uploaded file paths from session, or fall back to defaults
        $this->excelPath = Session::get('uploaded_excel_path', app_path('src/file.xlsx'));
        $this->pdfPath = Session::get('uploaded_pdf_path', app_path('src/Luminarc.pdf'));
        $this->pdfImageExtractor = new PdfImageExtractor($this->pdfPath);
    }

    public function index()
    {
        $products = $this->processData();
        Log::info('Processed products data', ['products' => $products]);

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function downloadPdf()
    {
        // 1. Get your data
        $products = $this->processData();
        $Arabic = new Arabic;
        // 2. Load the view and set paper/options

        foreach ($products as &$item) {
            if (isset($item['data']['description'])) {
                // utf8Glyphs joins the letters and reverses them for RTL
                $item['data']['description'] = $Arabic->utf8Glyphs($item['data']['description']);
                $item['data']['size'] = $Arabic->utf8Glyphs($item['data']['size']);
                $item['data']['unit'] = $Arabic->utf8Glyphs($item['data']['unit']);
            }
            // Repeat this for any other Arabic fields like 'unit' or 'category'
        }
        $pdf = Pdf::loadView('products.pdf', compact('products', 'Arabic'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'serif', // Fallback
                'chroot' => base_path(),   // Allow access to storage folder
            ]);

        $filename = $this->getFilename();
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Clear the images directory after PDF generation
        FileHelper::clearDirectory(public_path('images'));

        // 3. Return as a download
        return $pdf->download($filename);
    }

    public function downloadAndReset()
    {
        // Generate PDF
        $products = $this->processData();
        $Arabic = new Arabic;

        foreach ($products as &$item) {
            $item['data']['description'] = $item['data']['description'] ? $Arabic->utf8Glyphs($item['data']['description']) : $item['data']['code'];
            $item['data']['size'] = $Arabic->utf8Glyphs($item['data']['size']);
            $item['data']['unit'] = $Arabic->utf8Glyphs($item['data']['unit']);
        }

        $pdf = Pdf::loadView('products.pdf', compact('products', 'Arabic'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'serif',
                'chroot' => base_path(),
            ]);

        $filename = $this->getFilename();
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Clear the images directory after PDF generation
        FileHelper::clearDirectory(public_path('images'));

        // Clean up uploaded files
        $this->cleanupUploadedFiles();

        // Download the PDF
        return $pdf->download($filename);
    }

    private function getFilename()
    {
        $excelName = Session::get('uploaded_excel_name', pathinfo($this->excelPath, PATHINFO_FILENAME));

        return $excelName.'_catalog.pdf';
    }

    private function processData()
    {
        // Read Excel file
        $spreadsheet = IOFactory::load($this->excelPath);
        $worksheet = $spreadsheet->getActiveSheet();

        $products = [];

        // Start from row 2 (skip header)
        foreach ($worksheet->getRowIterator() as $index => $row) {
            if ($index == 1) {
                continue;
            } // Skip header row

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cellIterator as $cell) {
                $column = $cell->getColumn();
                if ($cell->isFormula()) {
                    try {
                        $value = $cell->getCalculatedValue();
                    } catch (\Throwable $e) {
                        $value = $cell->getOldCalculatedValue() ?? $cell->getValue();
                    }
                } else {
                    $value = $cell->getValue();
                }

                switch ($column) {
                    case 'A':
                        $data['page'] = $value;
                        break;
                    case 'B':
                        $data['description'] = $value;
                        break;
                    case 'C':
                        $data['size'] = $value;
                        break;
                    case 'D':
                        $data['unit'] = $value;
                        break;
                    case 'E':
                        $data['price'] = $value;
                        break;
                    case 'F':
                        $data['code'] = $value;
                        break;
                    case 'G':
                        $data['pdf_description'] = $value;
                        break;
                }
            }

            if (isset($data['page']) && $data['page']) {
                // Extract image from PDF
                $image = $this->extractImageFromPdf($data['page']);

                $products[] = [
                    'data' => $data,
                    'image' => $image,
                ];
            }
        }

        return $products;
    }

    private function extractImageFromPdf($pageNumber)
    {
        return $this->pdfImageExtractor->extractPageAsImage($pageNumber);
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
    }
}
