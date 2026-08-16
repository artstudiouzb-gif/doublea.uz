<?php

declare(strict_types=1);

namespace App\Core;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Пакетно достраивает WebP-варианты для старых публичных загрузок. */
final class ImageBatchOptimizer
{
    /**
     * @return array{scanned: int, optimized: int, planned: int, skipped: int, failed: int}
     */
    public static function run(string $directory, bool $dryRun = false, bool $force = false, int $limit = 0): array
    {
        $result = ['scanned' => 0, 'optimized' => 0, 'planned' => 0, 'skipped' => 0, 'failed' => 0];
        $directory = rtrim($directory, '/\\');
        if ($directory === '' || !is_dir($directory)) {
            throw new \InvalidArgumentException('Каталог публичных загрузок не найден: ' . $directory);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $result['scanned']++;
            $path = $file->getPathname();
            $info = @getimagesize($path);
            if ($info === false || (int) $info[0] < 1 || (int) $info[1] < 1) {
                $result['failed']++;
                continue;
            }

            if (!$force && self::variantsAreFresh($path, (int) $info[0])) {
                $result['skipped']++;
                continue;
            }
            if ($limit > 0 && $result['planned'] + $result['optimized'] >= $limit) {
                break;
            }

            if ($dryRun) {
                $result['planned']++;
                continue;
            }

            // Старые оригиналы не перезаписываем: пакетная миграция должна быть
            // обратимой, а экономию трафика обеспечивают новые WebP-варианты.
            Uploader::optimizeImage($path, false);
            if (self::variantsAreFresh($path, (int) $info[0])) {
                $result['optimized']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    private static function variantsAreFresh(string $path, int $width): bool
    {
        $base = preg_replace('/\.[^.]+$/', '', $path) ?? $path;
        $expected = [$base . '.webp'];
        if ($width > 800) {
            $expected[] = $base . '-800.webp';
        }
        if ($width > 1600) {
            $expected[] = $base . '-1600.webp';
        }

        $sourceMtime = (int) @filemtime($path);
        foreach ($expected as $variant) {
            if (!is_file($variant) || (int) @filemtime($variant) < $sourceMtime) {
                return false;
            }
        }

        return true;
    }
}
