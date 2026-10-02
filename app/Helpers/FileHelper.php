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

        $totalStart = microtime(true);
        $gsExe = self::findGhostscript();

        if ($gsExe) {
            $pageCount = self::extractWithGhostscript($pdfPath, $outputDir, $gsExe);
        } else {
            $pageCount = self::extractWithImagick($pdfPath, $outputDir);
        }

        \Illuminate\Support\Facades\Log::info('PDF extraction complete', [
            'pages' => $pageCount,
            'method' => $gsExe ? 'ghostscript' : 'imagick',
            'total_ms' => round((microtime(true) - $totalStart) * 1000),
        ]);

        return $pageCount;
    }

    private static function extractWithGhostscript($pdfPath, $outputDir, $gsExe)
    {
        $outputPattern = $outputDir.DIRECTORY_SEPARATOR.'page_%d.jpg';
        $cmd = self::quoteArg($gsExe)
            .' -dBATCH -dNOPAUSE -dSAFER -sDEVICE=jpeg -r72 -dJPEGQ=65'
            .' -sOutputFile='.self::quoteArg($outputPattern)
            .' '.self::quoteArg($pdfPath);

        \Illuminate\Support\Facades\Log::info('GS command', ['cmd' => $cmd]);

        exec($cmd.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            $detail = implode("\n", array_slice($output, -5));
            throw new \Exception('Ghostscript failed (exit '.$exitCode.'): '.$detail);
        }

        $pages = glob($outputDir.'/page_*.jpg');

        return $pages ? count($pages) : 0;
    }

    private static function extractWithImagick($pdfPath, $outputDir)
    {
        try {
            $pageCount = self::getPageCount($pdfPath);

            for ($i = 0; $i < $pageCount; $i++) {
                $page = new \Imagick;
                $page->setResolution(72, 72);
                $page->readImage($pdfPath.'['.$i.']');
                $page->setImageBackgroundColor('white');
                $flattened = $page->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $flattened->setImageFormat('jpeg');
                $flattened->setImageCompressionQuality(65);
                $flattened->writeImage($outputDir.'/page_'.($i + 1).'.jpg');
                $flattened->clear(); $flattened->destroy();
                $page->clear(); $page->destroy();
                unset($flattened, $page);

                if ($i % 10 === 0) {
                    gc_collect_cycles();
                }
            }

            return $pageCount;
        } catch (\Exception $e) {
            throw new \Exception('Error extracting images: '.$e->getMessage());
        }
    }

    private static function findGhostscript()
    {
        // 1. ENV override
        $env = env('GHOSTSCRIPT_PATH');
        if ($env && file_exists($env)) {
            return $env;
        }

        // 2. PATH lookup
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWindows) {
            $found = trim((string) shell_exec('where gswin64c 2>NUL'));
            if ($found && file_exists(strtok($found, "\n"))) {
                return strtok($found, "\n");
            }
            $found = trim((string) shell_exec('where gswin32c 2>NUL'));
            if ($found && file_exists(strtok($found, "\n"))) {
                return strtok($found, "\n");
            }
        } else {
            $found = trim((string) shell_exec('which gs 2>/dev/null'));
            if ($found && file_exists($found)) {
                return $found;
            }
        }

        // 3. Common Windows paths
        $candidates = [
            'C:\\Ghostscript\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\bin\\gswin64c.exe',
        ];
        foreach (glob('C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe') ?: [] as $p) {
            $candidates[] = $p;
        }
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function quoteArg($s)
    {
        return '"'.str_replace('"', '\\"', $s).'"';
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
