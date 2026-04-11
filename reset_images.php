<?php

// Master script to clear images and regenerate them from PDF

echo "=== Image Regeneration Process ===\n\n";

// Step 1: Clear images folder
echo "Step 1: Clearing images folder...\n";
$clearResult = exec('php clear_images.php', $clearOutput, $clearReturn);
echo implode("\n", $clearOutput)."\n\n";

if ($clearReturn !== 0) {
    echo "Error: Failed to clear images folder\n";
    exit(1);
}

// Step 2: Extract images from PDF
echo "Step 2: Extracting images from PDF...\n";
$pdfPath = 'app/src/images.pdf';
$outputDir = 'public/images';

// Check if PDF exists
if (! file_exists($pdfPath)) {
    echo "Error: PDF file not found at $pdfPath\n";
    exit(1);
}

// Ensure output directory exists
if (! file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created directory: $outputDir\n";
}

// Run PHP script
$phpCmd = "php extract_images.php \"$pdfPath\" \"$outputDir\"";
echo "Running: $phpCmd\n";
exec($phpCmd, $phpOutput, $phpReturn);
echo implode("\n", $phpOutput)."\n\n";

if ($phpReturn !== 0) {
    echo "Error: Failed to extract images from PDF\n";
    exit(1);
}

// Step 3: Regenerate images_data.php
echo "Step 3: Regenerating images_data.php...\n";
exec('php regenerate_images.php', $regenerateOutput, $regenerateReturn);
echo implode("\n", $regenerateOutput)."\n\n";

if ($regenerateReturn !== 0) {
    echo "Error: Failed to regenerate images_data.php\n";
    exit(1);
}

echo "=== Image regeneration completed successfully! ===\n";
