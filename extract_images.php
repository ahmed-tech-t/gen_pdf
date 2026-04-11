<?php

// Simple PHP script to extract images from PDF using Imagick

function extractImagesFromPdf($pdfPath, $outputDir)
{
    // Create output directory if it doesn't exist
    if (! file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    echo "Extracting images from: $pdfPath\n";
    echo "Output directory: $outputDir\n";

    // Clear existing images
    $existingFiles = glob($outputDir.'/*.jpg');
    if (! empty($existingFiles)) {
        foreach ($existingFiles as $file) {
            unlink($file);
        }
        echo 'Cleared '.count($existingFiles)." existing images\n";
    }

    try {
        $imagick = new Imagick;
        $imagick->setResolution(150, 150);
        $imagick->readImage($pdfPath);
        $pageCount = $imagick->getNumberImages();

        echo "PDF has $pageCount pages\n";

        for ($i = 0; $i < $pageCount; $i++) {
            $imagick->setIteratorIndex($i);
            $outputPath = $outputDir.'/page_'.($i + 1).'.jpg';
            $imagick->setImageFormat('jpeg');
            $imagick->writeImage($outputPath);
            echo 'Saved page '.($i + 1).' as: page_'.($i + 1).".jpg\n";
        }

        $imagick->clear();
        $imagick->destroy();

        return $pageCount;

    } catch (Exception $e) {
        echo 'Error extracting images: '.$e->getMessage()."\n";

        return false;
    }
}

if (php_sapi_name() === 'cli') {
    if ($argc < 3) {
        echo "Usage: php extract_images.php <pdf_path> <output_dir>\n";
        exit(1);
    }

    $pdfPath = $argv[1];
    $outputDir = $argv[2];

    if (! file_exists($pdfPath)) {
        echo "Error: PDF file not found: $pdfPath\n";
        exit(1);
    }

    $result = extractImagesFromPdf($pdfPath, $outputDir);

    if ($result !== false) {
        echo "Successfully extracted $result images\n";
        exit(0);
    } else {
        echo "Failed to extract images\n";
        exit(1);
    }
}
