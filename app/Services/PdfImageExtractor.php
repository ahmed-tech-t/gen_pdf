<?php

namespace App\Services;

class PdfImageExtractor
{
    protected $pdfPath;

    public function __construct($pdfPath)
    {
        $this->pdfPath = $pdfPath;
    }

    public function getImageUrl($pageNumber)
    {
        $imagePath = public_path('images/page_'.$pageNumber.'.jpg');

        if (file_exists($imagePath)) {
            return '/images/page_'.$pageNumber.'.jpg';
        }

        return null;
    }

    public function getImagePath($pageNumber)
    {
        $imagePath = public_path('images/page_'.$pageNumber.'.jpg');

        if (file_exists($imagePath)) {
            return $imagePath;
        }

        return null;
    }

    public function hasImagick()
    {
        return extension_loaded('imagick') && class_exists('Imagick');
    }
}
