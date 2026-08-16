<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

final class OpenGraphHelper
{
    /**
     * Генерирует Open Graph и Twitter Card метаданные.
     *
     * Локальные изображения выводятся только после проверки физического
     * файла в public/. Внешние HTTPS URL не запрашиваются во время рендера:
     * такая проверка замедляла бы каждый публичный ответ и создавала SSRF-риск.
     */
    public static function render(
        string $appUrl,
        string $title,
        string $description,
        string $canonicalUrl,
        string $currentLang = 'ru',
        string $image = '',
        string $type = 'website',
        ?string $publishedTime = null
    ): string {
        $siteName = Setting::getLocalized(
            'site_name',
            Locale::current(),
            'Государственный портал Республики Узбекистан'
        );

        $resolvedImage = self::resolveFirstImage($appUrl, [
            $image,
            Setting::get('default_og_image', ''),
            Setting::get('og_default_image', ''),
            Setting::get('logo_url', ''),
        ]);

        $locales = [
            'ru' => 'ru_RU',
            'uz' => 'uz_UZ',
            'en' => 'en_US',
        ];
        $locale = $locales[$currentLang] ?? 'ru_RU';

        $html = "\n<!-- Rich Open Graph & Social Cards -->\n";
        $html .= self::meta('property', 'og:site_name', $siteName);
        $html .= self::meta('property', 'og:locale', $locale);
        $html .= self::meta('property', 'og:type', $type);
        $html .= self::meta('property', 'og:title', $title);

        if ($description !== '') {
            $html .= self::meta('property', 'og:description', $description);
        }

        $html .= self::meta('property', 'og:url', $canonicalUrl);

        if ($resolvedImage !== null) {
            $html .= self::meta('property', 'og:image', $resolvedImage['url']);
            if (str_starts_with($resolvedImage['url'], 'https://')) {
                $html .= self::meta('property', 'og:image:secure_url', $resolvedImage['url']);
            }
            if ($resolvedImage['width'] !== null && $resolvedImage['height'] !== null) {
                $html .= self::meta('property', 'og:image:width', (string) $resolvedImage['width']);
                $html .= self::meta('property', 'og:image:height', (string) $resolvedImage['height']);
            }
            $html .= self::meta('property', 'og:image:alt', $title);
        }

        if ($publishedTime !== null && $publishedTime !== '') {
            $time = strtotime($publishedTime);
            if ($time !== false) {
                $html .= self::meta('property', 'article:published_time', date('c', $time));
            }
        }

        $html .= self::meta(
            'name',
            'twitter:card',
            $resolvedImage !== null ? 'summary_large_image' : 'summary'
        );
        $html .= self::meta('name', 'twitter:title', $title);
        if ($description !== '') {
            $html .= self::meta('name', 'twitter:description', $description);
        }
        if ($resolvedImage !== null) {
            $html .= self::meta('name', 'twitter:image', $resolvedImage['url']);
        }

        return $html;
    }

    private static function meta(string $attribute, string $name, string $content): string
    {
        return sprintf(
            '<meta %s="%s" content="%s">' . "\n",
            $attribute,
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($content, ENT_QUOTES)
        );
    }

    /**
     * @param list<string> $candidates
     * @return array{url:string,width:int|null,height:int|null}|null
     */
    private static function resolveFirstImage(string $appUrl, array $candidates): ?array
    {
        foreach ($candidates as $candidate) {
            $resolved = self::resolveImage($appUrl, trim($candidate));
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /** @return array{url:string,width:int|null,height:int|null}|null */
    private static function resolveImage(string $appUrl, string $candidate): ?array
    {
        if ($candidate === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $candidate) === 1) {
            $candidateParts = parse_url($candidate);
            $appParts = parse_url($appUrl);
            if (!is_array($candidateParts) || !is_array($appParts)) {
                return null;
            }

            $candidateHost = strtolower((string) ($candidateParts['host'] ?? ''));
            $appHost = strtolower((string) ($appParts['host'] ?? ''));
            if ($candidateHost !== $appHost) {
                // Внешний URL считается явной настройкой редактора. Не делаем
                // сетевой HEAD-запрос при каждом открытии страницы.
                return [
                    'url' => $candidate,
                    'width' => null,
                    'height' => null,
                ];
            }

            $path = (string) ($candidateParts['path'] ?? '');
            $query = isset($candidateParts['query']) ? '?' . $candidateParts['query'] : '';
        } else {
            if (str_starts_with($candidate, '//')
                || preg_match('#^[a-z][a-z0-9+.-]*:#i', $candidate) === 1) {
                return null;
            }
            $relativeParts = parse_url('/' . ltrim($candidate, '/'));
            if (!is_array($relativeParts)) {
                return null;
            }
            $path = (string) ($relativeParts['path'] ?? '');
            $query = isset($relativeParts['query']) ? '?' . $relativeParts['query'] : '';
        }

        $appParts = parse_url($appUrl);
        if (!is_array($appParts)
            || !in_array(strtolower((string) ($appParts['scheme'] ?? '')), ['http', 'https'], true)
            || trim((string) ($appParts['host'] ?? '')) === '') {
            return null;
        }

        $local = self::localImage($path);
        if ($local === null) {
            return null;
        }

        return [
            'url' => rtrim($appUrl, '/') . '/' . ltrim($path, '/') . $query,
            'width' => $local['width'],
            'height' => $local['height'],
        ];
    }

    /** @return array{width:int|null,height:int|null}|null */
    private static function localImage(string $urlPath): ?array
    {
        if (!defined('APP_ROOT') || $urlPath === '' || str_contains($urlPath, "\0")) {
            return null;
        }

        $decodedPath = rawurldecode($urlPath);
        $extension = strtolower(pathinfo($decodedPath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
            return null;
        }

        $publicRoot = realpath(APP_ROOT . '/public');
        $file = realpath(APP_ROOT . '/public/' . ltrim($decodedPath, '/'));
        if ($publicRoot === false
            || $file === false
            || !is_file($file)
            || !str_starts_with($file, $publicRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $size = @getimagesize($file);

        return [
            'width' => is_array($size) ? (int) $size[0] : null,
            'height' => is_array($size) ? (int) $size[1] : null,
        ];
    }
}
