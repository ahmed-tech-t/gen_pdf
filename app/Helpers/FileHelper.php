<?php

namespace App\Helpers;

class FileHelper
{
    public static function clearDirectory($directory)
    {
        if (is_dir($directory)) {
            $files = glob($directory.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public static function getPageCount($pdfPath)
    {
        $imagick = new \Imagick;
        $imagick->pingImage($pdfPath);
        $pageCount = $imagick->getNumberImages();
        $imagick->clear();
        $imagick->destroy();
        unset($imagick);

        return $pageCount;
    }

    public static function extractImagesFromPdf($pdfPath, $outputDir)
    {
        if (! extension_loaded('imagick') || ! class_exists('Imagick')) {
            throw new \Exception('Imagick extension is not installed or enabled');
        }

        if (! file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        self::clearDirectory($outputDir);

        try {
            $pageCount = self::getPageCount($pdfPath);

            for ($i = 1; $i <= $pageCount; $i++) {
                $pageImagick = new \Imagick;
                $pageImagick->setResolution(120, 120);
                $pageImagick->readImage($pdfPath.'['.($i - 1).']');
                $pageImagick->setImageFormat('jpeg');
                $pageImagick->setImageCompressionQuality(80);
                $pageImagick->writeImage($outputDir.'/page_'.$i.'.jpg');
                $pageImagick->clear();
                $pageImagick->destroy();
                unset($pageImagick);

                if ($i % 10 === 0) {
                    gc_collect_cycles();
                }
            }

            return $pageCount;

        } catch (\Exception $e) {
            throw new \Exception('Error extracting images: '.$e->getMessage());
        }
    }

    public static function hasImagick()
    {
        return extension_loaded('imagick') && class_exists('Imagick');
    }

    public static function getFileSize($filePath)
    {
        if (! file_exists($filePath)) {
            return 'File not found';
        }

        $bytes = filesize($filePath);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($bytes == 0) {
            return '0 '.$units[0];
        }

        $exponent = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $exponent), 2).' '.$units[$exponent];
    }

    public static function validateFileType($filePath, array $allowedMimeTypes)
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $mimeType = mime_content_type($filePath);

        return in_array($mimeType, $allowedMimeTypes);
    }

    public static function createSecureFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9\-._]/', '', $filename);
        $filename = preg_replace('/\s+/', '_', $filename);
        $filename = strtolower($filename);

        return $filename;
    }
}
