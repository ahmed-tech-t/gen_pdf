<?php

namespace App\Helpers;

class FileHelper
{
    /**
     * Clear all files in a directory
     *
     * @param  string  $directory
     * @return void
     */
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

    /**
     * Extract images from PDF file using Imagick
     *
     * @param  string  $pdfPath
     * @param  string  $outputDir
     * @return int|false Number of pages extracted or false on failure
     */
    public static function extractImagesFromPdf($pdfPath, $outputDir)
    {
        if (! extension_loaded('imagick') || ! class_exists('Imagick')) {
            throw new \Exception('Imagick extension is not installed or enabled');
        }

        // Create output directory if it doesn't exist
        if (! file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Clear existing images
        self::clearDirectory($outputDir);

        try {
            $imagick = new \Imagick;
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath);
            $pageCount = $imagick->getNumberImages();

            for ($i = 0; $i < $pageCount; $i++) {
                $imagick->setIteratorIndex($i);
                $outputPath = $outputDir.'/page_'.($i + 1).'.jpg';
                $imagick->setImageFormat('jpeg');
                $imagick->writeImage($outputPath);
            }

            $imagick->clear();
            $imagick->destroy();

            return $pageCount;

        } catch (\Exception $e) {
            throw new \Exception('Error extracting images: '.$e->getMessage());
        }
    }

    /**
     * Check if Imagick extension is available
     *
     * @return bool
     */
    public static function hasImagick()
    {
        return extension_loaded('imagick') && class_exists('Imagick');
    }

    /**
     * Get file size in human readable format
     *
     * @param  string  $filePath
     * @return string
     */
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

    /**
     * Validate file type
     *
     * @param  string  $filePath
     * @return bool
     */
    public static function validateFileType($filePath, array $allowedMimeTypes)
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $mimeType = mime_content_type($filePath);

        return in_array($mimeType, $allowedMimeTypes);
    }

    /**
     * Create a secure filename
     *
     * @param  string  $filename
     * @return string
     */
    public static function createSecureFilename($filename)
    {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9\-._]/', '', $filename);
        $filename = preg_replace('/\s+/', '_', $filename);

        // Convert to lowercase
        $filename = strtolower($filename);

        return $filename;
    }
}
