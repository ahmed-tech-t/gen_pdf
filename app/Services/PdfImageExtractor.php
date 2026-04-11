<?php

namespace App\Services;

class PdfImageExtractor
{
    protected $pdfPath;

    public function __construct($pdfPath)
    {
        $this->pdfPath = $pdfPath;
    }

    public function extractPageAsImage($pageNumber)
    {
        try {
            // Use the pre-extracted JPG images from public/images/ directory
            $imagePath = public_path('images/page_'.$pageNumber.'.jpg');

            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
                if ($imageData !== false) {
                    return base64_encode($imageData);
                }
            }

            // Fallback to placeholder if image doesn't exist
            return $this->createPlaceholderImage($pageNumber);

        } catch (\Exception $e) {
            return $this->createPlaceholderImage($pageNumber);
        }
    }

    // This method is no longer needed since we're using pre-extracted images
    // private function extractImageFromPdfContent($pdfContent, $pageNumber)
    // {
    //     // Removed - using pre-extracted JPG images instead
    // }

    private function createPlaceholderImage($pageNumber)
    {
        // Create a simple SVG placeholder as base64
        $svg = '<svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="300" height="200" fill="#f3f4f6"/>
            <text x="150" y="90" font-family="Arial" font-size="16" fill="#6b7280" text-anchor="middle">Product '.$pageNumber.'</text>
            <text x="150" y="110" font-family="Arial" font-size="12" fill="#9ca3af" text-anchor="middle">Page '.$pageNumber.'</text>
            <text x="150" y="130" font-family="Arial" font-size="10" fill="#d1d5db" text-anchor="middle">From PDF</text>
            <rect x="10" y="180" width="280" height="1" fill="#e5e7eb"/>
            <text x="150" y="195" font-family="Arial" font-size="10" fill="#d1d5db" text-anchor="middle">Luminarc Collection</text>
        </svg>';

        return base64_encode($svg);
    }

    public function hasImagick()
    {
        return extension_loaded('imagick') && class_exists('Imagick');
    }
}
