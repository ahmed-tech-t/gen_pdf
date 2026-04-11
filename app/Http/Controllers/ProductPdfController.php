<?php

namespace App\Http\Controllers;

use App\Services\PdfImageExtractor;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductPdfController extends Controller
{
    protected $excelPath;

    protected $pdfPath;

    protected $pdfImageExtractor;

    public function __construct()
    {
        $this->excelPath = app_path('src/file.xlsx');
        $this->pdfPath = app_path('src/Luminarc.pdf');
        $this->pdfImageExtractor = new PdfImageExtractor($this->pdfPath);
    }

    public function index()
    {
        $products = $this->processData();

        return view('products.index', [
            'products' => $products,
        ]);
    }


    public function downloadPdf()
    {
        // 1. Get your data
        $products = $this->processData();
        $Arabic = new Arabic();
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

        $filename = "catalog-" . time() . ".pdf";
        if (ob_get_length())
            ob_end_clean();
        // 3. Return as a download
        return $pdf->download($filename);
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
                $value = $cell->getValue();

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
}
