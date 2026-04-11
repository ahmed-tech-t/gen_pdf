<?php

// Script to clear the public/images folder

$imageDir = 'public/images';

if (is_dir($imageDir)) {
    $files = glob($imageDir.'/*');

    if (count($files) > 0) {
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    echo 'Deleted: '.basename($file)."\n";
                } else {
                    echo 'Failed to delete: '.basename($file)."\n";
                }
            }
        }
        echo 'Cleared '.count($files)." files from $imageDir\n";
    } else {
        echo "No files to clear in $imageDir\n";
    }
} else {
    echo "Directory $imageDir does not exist\n";
}

echo "Image folder cleared successfully.\n";
