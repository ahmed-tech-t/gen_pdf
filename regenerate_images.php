<?php

// Regenerate images_data.php with proper base64 encoded JPG images

$imageData = [];

// Loop through all 40 images
for ($i = 1; $i <= 40; $i++) {
    $imagePath = 'public/images/page_'.$i.'.jpg';

    if (file_exists($imagePath)) {
        $imageContent = file_get_contents($imagePath);
        if ($imageContent !== false) {
            $imageData[$i] = base64_encode($imageContent);
            echo "Successfully encoded image: page_$i.jpg\n";
        } else {
            echo "Failed to read image: page_$i.jpg\n";
        }
    } else {
        echo "Image not found: page_$i.jpg\n";
    }
}

// Generate PHP file content
$phpContent = "<?php\n";
$phpContent .= "// Generated base64 image data for PDF pages\n";
$phpContent .= "// Contains actual JPG images extracted from Luminarc.pdf\n";
$phpContent .= "\$imageData = array (\n";

foreach ($imageData as $page => $base64) {
    $phpContent .= "  $page => '$base64',\n";
}

$phpContent .= ");\n";

// Write to file
if (file_put_contents('public/images_data.php', $phpContent)) {
    echo "\nSuccessfully generated public/images_data.php with ".count($imageData)." images\n";
} else {
    echo "\nFailed to write images_data.php\n";
}
