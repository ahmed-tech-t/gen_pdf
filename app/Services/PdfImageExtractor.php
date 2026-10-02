<?php

namespace App\Services;

class PdfImageExtractor
{
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
