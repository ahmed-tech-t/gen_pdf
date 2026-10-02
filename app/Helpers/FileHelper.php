<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

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

    const IMAGE_DPI = 96;

    const IMAGE_MAX_WIDTH = 1200;

    const IMAGE_QUALITY = 75;

    const MAX_WORKERS = 8;

    const CACHE_ENTRIES = 5;

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
            $start = microtime(true);

            $hashStart = microtime(true);
            $cacheKey = md5(md5_file($pdfPath).'|'.self::IMAGE_DPI.'|'.self::IMAGE_MAX_WIDTH.'|'.self::IMAGE_QUALITY);
            $hashSeconds = round(microtime(true) - $hashStart, 2);

            $cached = self::restoreFromCache($cacheKey, $outputDir);
            if ($cached !== null) {
                Log::info('PDF image extraction finished', [
                    'method' => 'cache',
                    'pages' => $cached,
                    'hash_seconds' => $hashSeconds,
                    'total_seconds' => round(microtime(true) - $start, 2),
                ]);

                return $cached;
            }

            $method = 'ghostscript';
            $pageCount = self::extractWithGhostscript($pdfPath, $outputDir);

            if ($pageCount === null) {
                $method = 'imagick';
                $pageCount = self::extractWithImagick($pdfPath, $outputDir);
            }

            self::storeInCache($cacheKey, $outputDir);

            Log::info('PDF image extraction finished', [
                'method' => $method,
                'pages' => $pageCount,
                'hash_seconds' => $hashSeconds,
                'total_seconds' => round(microtime(true) - $start, 2),
            ]);

            return $pageCount;

        } catch (\Exception $e) {
            throw new \Exception('Error extracting images: '.$e->getMessage());
        }
    }

    /**
     * Renders pages with several Ghostscript processes in parallel, each handling a
     * contiguous page range. Returns null when Ghostscript is unavailable or fails.
     */
    private static function extractWithGhostscript($pdfPath, $outputDir)
    {
        $gs = self::findGhostscript();
        if ($gs === null) {
            return null;
        }

        $pageCount = self::getPageCountWithGhostscript($gs, $pdfPath);
        $workers = $pageCount ? max(1, min(self::cpuCount() - 1, self::MAX_WORKERS, $pageCount)) : 1;
        $ranges = $pageCount ? self::splitPages($pageCount, $workers) : [[null, null]];

        $renderStart = microtime(true);
        $processes = [];
        foreach ($ranges as $index => [$first, $last]) {
            $workerDir = $outputDir.'/_worker'.$index;
            if (! is_dir($workerDir)) {
                mkdir($workerDir, 0755, true);
            }

            $command = [$gs, '-dSAFER', '-dBATCH', '-dNOPAUSE', '-dQUIET',
                '-sDEVICE=jpeg',
                '-dJPEGQ='.self::IMAGE_QUALITY,
                '-r'.self::IMAGE_DPI,
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4',
            ];
            if ($first !== null) {
                $command[] = '-dFirstPage='.$first;
                $command[] = '-dLastPage='.$last;
            }
            $command[] = '-sOutputFile='.$workerDir.'/p_%d.jpg';
            $command[] = $pdfPath;

            $processes[$index] = self::startProcess($command);
        }

        $failures = [];
        foreach ($processes as $index => $process) {
            [$exitCode, , $stderr] = self::waitProcess($process);
            if ($exitCode !== 0) {
                $failures[] = ['worker' => $index, 'exit_code' => $exitCode, 'stderr' => $stderr];
            }
        }

        $total = 0;
        foreach ($ranges as $index => [$first]) {
            $workerDir = $outputDir.'/_worker'.$index;
            $files = glob($workerDir.'/p_*.jpg') ?: [];
            foreach ($files as $file) {
                $n = (int) substr(basename($file, '.jpg'), 2);
                rename($file, $outputDir.'/page_'.(($first ?? 1) + $n - 1).'.jpg');
                $total++;
            }
            self::deleteDirectory($workerDir);
        }

        if (! empty($failures) || $total === 0 || ($pageCount && $total !== $pageCount)) {
            Log::warning('Ghostscript extraction failed, falling back to Imagick', [
                'expected_pages' => $pageCount,
                'rendered_pages' => $total,
                'failures' => $failures,
            ]);
            self::clearDirectory($outputDir);

            return null;
        }

        $renderSeconds = round(microtime(true) - $renderStart, 2);

        $downscaleStart = microtime(true);
        foreach (glob($outputDir.'/page_*.jpg') as $file) {
            self::downscaleImage($file);
        }

        Log::info('Ghostscript render stats', [
            'pages' => $total,
            'workers' => count($ranges),
            'render_seconds' => $renderSeconds,
            'downscale_seconds' => round(microtime(true) - $downscaleStart, 2),
        ]);

        return $total;
    }

    private static function getPageCountWithGhostscript($gs, $pdfPath)
    {
        $psPath = str_replace(['\\', '(', ')'], ['/', '\\(', '\\)'], $pdfPath);

        $process = self::startProcess([
            $gs, '-q', '-dNODISPLAY', '-dNOSAFER', '-dBATCH', '-dNOPAUSE',
            '-c', '('.$psPath.') (r) file runpdfbegin pdfpagecount = quit',
        ]);
        [$exitCode, $stdout] = self::waitProcess($process);

        if ($exitCode === 0 && preg_match('/(\d+)\s*$/', trim($stdout), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private static function splitPages($pageCount, $workers)
    {
        $ranges = [];
        $chunk = (int) ceil($pageCount / $workers);
        for ($first = 1; $first <= $pageCount; $first += $chunk) {
            $ranges[] = [$first, min($pageCount, $first + $chunk - 1)];
        }

        return $ranges;
    }

    private static function cpuCount()
    {
        $count = (int) getenv('NUMBER_OF_PROCESSORS');
        if ($count < 1 && is_readable('/proc/cpuinfo')) {
            $count = substr_count((string) file_get_contents('/proc/cpuinfo'), 'processor');
        }

        return max(1, $count);
    }

    private static function startProcess(array $command)
    {
        $stdoutFile = tempnam(sys_get_temp_dir(), 'gso');
        $stderrFile = tempnam(sys_get_temp_dir(), 'gse');

        $handle = proc_open($command, [
            1 => ['file', $stdoutFile, 'w'],
            2 => ['file', $stderrFile, 'w'],
        ], $pipes);

        return [$handle, $stdoutFile, $stderrFile];
    }

    private static function waitProcess(array $process)
    {
        [$handle, $stdoutFile, $stderrFile] = $process;

        $exitCode = is_resource($handle) ? proc_close($handle) : -1;
        $stdout = (string) @file_get_contents($stdoutFile);
        $stderr = (string) @file_get_contents($stderrFile);
        @unlink($stdoutFile);
        @unlink($stderrFile);

        return [$exitCode, $stdout, $stderr];
    }

    private static function cacheRoot()
    {
        return storage_path('app/pdf-cache');
    }

    private static function restoreFromCache($cacheKey, $outputDir)
    {
        $cacheDir = self::cacheRoot().'/'.$cacheKey;
        if (! is_file($cacheDir.'/.complete')) {
            return null;
        }

        $files = glob($cacheDir.'/page_*.jpg') ?: [];
        foreach ($files as $file) {
            copy($file, $outputDir.'/'.basename($file));
        }
        touch($cacheDir.'/.complete');

        return count($files) ?: null;
    }

    private static function storeInCache($cacheKey, $outputDir)
    {
        $root = self::cacheRoot();
        $cacheDir = $root.'/'.$cacheKey;
        $workDir = $cacheDir.'.tmp'.uniqid();

        try {
            mkdir($workDir, 0755, true);
            foreach (glob($outputDir.'/page_*.jpg') ?: [] as $file) {
                copy($file, $workDir.'/'.basename($file));
            }
            file_put_contents($workDir.'/.complete', date('c'));

            if (is_dir($cacheDir)) {
                self::deleteDirectory($cacheDir);
            }
            rename($workDir, $cacheDir);
        } catch (\Throwable $e) {
            Log::warning('Could not store extracted images in cache', ['error' => $e->getMessage()]);
            self::deleteDirectory($workDir);

            return;
        }

        self::pruneCache();
    }

    private static function pruneCache()
    {
        $entries = [];
        foreach (glob(self::cacheRoot().'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $marker = $dir.'/.complete';
            $entries[$dir] = is_file($marker) ? filemtime($marker) : 0;
        }

        arsort($entries);
        foreach (array_slice(array_keys($entries), self::CACHE_ENTRIES) as $dir) {
            self::deleteDirectory($dir);
        }
    }

    private static function deleteDirectory($dir)
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir.'/'.$item;
            is_dir($path) ? self::deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private static function extractWithImagick($pdfPath, $outputDir)
    {
        $pageCount = self::getPageCount($pdfPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $pageImagick = new \Imagick;
            $pageImagick->setResolution(self::IMAGE_DPI, self::IMAGE_DPI);
            $pageImagick->readImage($pdfPath.'['.($i - 1).']');
            if ($pageImagick->getImageWidth() > self::IMAGE_MAX_WIDTH) {
                $pageImagick->thumbnailImage(self::IMAGE_MAX_WIDTH, 0);
            }
            $pageImagick->stripImage();
            $pageImagick->setImageFormat('jpeg');
            $pageImagick->setImageCompressionQuality(self::IMAGE_QUALITY);
            $pageImagick->writeImage($outputDir.'/page_'.$i.'.jpg');
            $pageImagick->clear();
            $pageImagick->destroy();
            unset($pageImagick);

            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }

        return $pageCount;
    }

    private static function downscaleImage($file)
    {
        $size = @getimagesize($file);
        if ($size === false || $size[0] <= self::IMAGE_MAX_WIDTH) {
            return;
        }

        $image = new \Imagick;
        $image->setOption('jpeg:size', self::IMAGE_MAX_WIDTH.'x'.(int) ceil($size[1] * self::IMAGE_MAX_WIDTH / $size[0]));
        $image->readImage($file);
        $image->thumbnailImage(self::IMAGE_MAX_WIDTH, 0);
        $image->stripImage();
        $image->setImageCompressionQuality(self::IMAGE_QUALITY);
        $image->writeImage($file);
        $image->clear();
        $image->destroy();
    }

    private static function findGhostscript()
    {
        $configured = env('GHOSTSCRIPT_PATH');
        if ($configured && is_file($configured)) {
            return $configured;
        }

        $isWindows = PHP_OS_FAMILY === 'Windows';
        $names = $isWindows ? ['gswin64c.exe', 'gswin32c.exe'] : ['gs'];

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            foreach ($names as $name) {
                $candidate = rtrim($dir, '\\/').DIRECTORY_SEPARATOR.$name;
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        if ($isWindows) {
            $matches = array_merge(
                glob('C:/Program Files/gs/*/bin/gswin64c.exe') ?: [],
                glob('C:/Program Files (x86)/gs/*/bin/gswin32c.exe') ?: []
            );
            if (! empty($matches)) {
                rsort($matches, SORT_NATURAL);

                return $matches[0];
            }
        }

        return null;
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
