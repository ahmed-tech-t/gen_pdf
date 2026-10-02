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

    protected $pdfImageExtractor;

    public function __construct()
    {
        $this->excelPath = Session::get('uploaded_excel_path', public_path('uploads/uploaded.xlsx'));
        $this->pdfImageExtractor = new PdfImageExtractor;
    }

    public function index()
    {
        if (! file_exists($this->excelPath)) {
            return $this->redirectToUpload();
        }

        $products = $this->processData();
        Log::info('Processed products data', ['count' => count($products)]);

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function downloadPdf()
    {
        if (! file_exists($this->excelPath)) {
            return $this->redirectToUpload();
        }

        $pdf = $this->buildPdf($this->processData());

        $filename = $this->getFilename();
        if (ob_get_length()) {
            ob_end_clean();
        }

        $response = $pdf->download($filename);

        FileHelper::clearDirectory(public_path('images'));

        return $response;
    }

    public function downloadAndReset()
    {
        if (! file_exists($this->excelPath)) {
            return $this->redirectToUpload();
        }

        $pdf = $this->buildPdf($this->processData());

        $filename = $this->getFilename();
        if (ob_get_length()) {
            ob_end_clean();
        }

        $response = $pdf->download($filename);

        FileHelper::clearDirectory(public_path('images'));
        $this->cleanupUploadedFiles();

        return $response;
    }

    private function buildPdf(array $products)
    {
        $Arabic = new Arabic;

        foreach ($products as &$item) {
            $item['data']['description'] = $item['data']['description']
                ? $Arabic->utf8Glyphs($item['data']['description'])
                : $item['data']['code'];
            $item['data']['size'] = $Arabic->utf8Glyphs((string) $item['data']['size']);
            $item['data']['unit'] = $Arabic->utf8Glyphs((string) $item['data']['unit']);
        }
        unset($item);

        return Pdf::loadView('products.pdf', compact('products', 'Arabic'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isFontSubsettingEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'serif',
                'chroot' => base_path(),
            ]);
    }

    private function redirectToUpload()
    {
        return redirect()->route('landing')
            ->with('error', 'Uploaded files were not found. Please upload your PDF and Excel files again.');
    }

    private function getFilename()
    {
        $pdfName = Session::get('uploaded_pdf_name', 'Catalog');

        return $pdfName.'_Catalog.pdf';
    }

    private function processData()
    {
        $reader = IOFactory::createReaderForFile($this->excelPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($this->excelPath);
        $worksheet = $spreadsheet->getActiveSheet();

        $products = [];

        foreach ($worksheet->getRowIterator() as $index => $row) {
            if ($index == 1) {
                continue;
            }

            $cellIterator = $row->getCellIterator('A', 'G');
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
                $imageUrl = $this->pdfImageExtractor->getImageUrl($data['page']);
                $imagePath = $this->pdfImageExtractor->getImagePath($data['page']);

                $products[] = [
                    'data' => $data,
                    'image' => $imageUrl,
                    'image_path' => $imagePath,
                ];
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $products;
    }

    private function cleanupUploadedFiles()
    {
        $uploadsDir = public_path('uploads');

        if (is_dir($uploadsDir)) {
            $files = glob($uploadsDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        Session::forget('uploaded_excel_path');
        Session::forget('uploaded_excel_name');
        Session::forget('uploaded_pdf_name');
    }
}
