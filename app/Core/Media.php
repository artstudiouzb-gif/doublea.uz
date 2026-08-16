<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Вывод адаптивных изображений через <picture>. Если файл — локальная загрузка,
 * для которой Uploader::optimizeImage сгенерировал WebP-разрешения
 * (name-800.webp / name-1600.webp / name.webp), они подставляются в srcset.
 * Внешние URL и файлы без вариантов отдаются обычным <img> (graceful fallback).
 *
 * Фокальная точка (в %) кладётся в object-position — при object-fit: cover
 * ключевой объект остаётся в кадре на любых пропорциях.
 */
final class Media
{
    /** @var array<string, array{width: int, height: int}|null> */
    private static array $dimensionCache = [];

    /** @var array<string, array{full: ?string, w1600: ?string, w800: ?string}|null> */
    private static array $variantCache = [];

    /**
     * Автор снимка без служебного слова «Фото:» в начале: подпись выводится с
     * этим словом сама, а редактор естественно пишет его руками — и получалось
     * «Фото: Фото: пресс-служба».
     */
    public static function photoCredit(?string $credit): string
    {
        $credit = trim((string) $credit);
        if ($credit === '') {
            return '';
        }
        // Русское, узбекское и английское написание, с двоеточием или тире.
        $stripped = preg_replace('/^\s*(фото|foto|surat|photo)\s*[:—–-]\s*/iu', '', $credit);

        return trim((string) ($stripped ?? $credit));
    }

    public static function picture(
        ?string $url,
        string $alt = '',
        ?int $focalX = null,
        ?int $focalY = null,
        string $imgClass = '',
        bool $lazy = true,
        string $sizes = '(max-width: 800px) 100vw, 800px',
        bool $highPriority = false,
        string $pictureClass = '',
        ?string $mobileUrl = null
    ): string {
        $url = trim((string) $url);
        if ($url === '' || !UrlGuard::isSafeMedia($url)) {
            return '';
        }

        // Глобальный тумблер ленивой загрузки (Производительность). Отключение
        // делает все картинки «eager» (например, для специфичных лендингов).
        try {
            if (\App\Models\Setting::get('perf_lazy_load', '1') !== '1') {
                $lazy = false;
            }
        } catch (\Throwable) {
            // БД недоступна — оставляем как передано.
        }

        $altAttr = htmlspecialchars($alt, ENT_QUOTES);
        $classAttr = $imgClass !== '' ? ' class="' . htmlspecialchars($imgClass, ENT_QUOTES) . '"' : '';
        $loadingAttr = $lazy
            ? ' loading="lazy" decoding="async"'
            : ' loading="eager" decoding="async"';
        $priorityAttr = $highPriority ? ' fetchpriority="high"' : '';
        $pictureClassAttr = $pictureClass !== ''
            ? ' class="' . htmlspecialchars($pictureClass, ENT_QUOTES) . '"'
            : '';
        // Кадрирование задаётся двумя механизмами: классы `media-position--*`
        // (пресеты из админки) и inline-переменная `--media-object-position`
        // (автоподбор SmartCrop). В CSS правило классов идёт после правила
        // `[style*="--media-object-position"]`, поэтому при одинаковой
        // специфичности класс всегда выигрывает — inline-стиль в таком случае
        // не применяется и остаётся мёртвым атрибутом на каждой картинке.
        // Не выводим его, когда вызывающий код уже передал классы позиции.
        $hasPositionClasses = str_contains($imgClass, 'media-position--')
            || str_contains($pictureClass, 'media-position--');

        $styleAttr = '';
        if ($focalX !== null && $focalY !== null) {
            $fx = max(0, min(100, $focalX));
            $fy = max(0, min(100, $focalY));
            $styleAttr = ' style="--media-object-position:' . $fx . '% ' . $fy . '%"';
        } elseif (!$hasPositionClasses) {
            $focalPos = SmartCrop::focalPosition($url);
            $styleAttr = ' style="--media-object-position:' . htmlspecialchars($focalPos, ENT_QUOTES) . '"';
        }

        // Собственные размеры файла резервируют место под картинку: без них
        // содержимое прыгает, пока изображения грузятся (CLS).
        $dimensions = self::dimensions($url);
        $sizeAttr = $dimensions !== null
            ? ' width="' . $dimensions[0] . '" height="' . $dimensions[1] . '"'
            : '';

        $img = '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="' . $altAttr . '"'
            . $classAttr . $sizeAttr . $loadingAttr . $priorityAttr . $styleAttr . '>';

        // Отдельный кадр для телефона: источник с медиазапросом идёт первым,
        // и браузер скачивает ровно одну картинку — ту, что подойдёт экрану.
        $mobileSources = self::mobileSources($mobileUrl, $sizes);

        $variants = self::webpVariants($url);
        $srcset = $variants !== null ? self::webpSrcset($variants) : [];
        if ($srcset === []) {
            if ($mobileSources === '' && $pictureClass === '') {
                return $img;
            }
            if ($pictureClass === '') {
                $pictureClassAttr = ' class="media-picture"';
            }

            return '<picture' . $pictureClassAttr . '>' . $mobileSources . $img . '</picture>';
        }

        if ($pictureClass === '') {
            $pictureClassAttr = ' class="media-picture"';
        }

        return '<picture' . $pictureClassAttr . '>'
            . $mobileSources
            . '<source type="image/webp" srcset="' . implode(', ', $srcset) . '" '
            . 'sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '">'
            . $img
            . '</picture>';
    }

    /**
     * Источники для узкого экрана: сначала WebP-набор мобильного файла, затем
     * он же как есть. Оба с медиазапросом, поэтому на десктопе не скачиваются.
     */
    private static function mobileSources(?string $mobileUrl, string $sizes): string
    {
        $mobileUrl = trim((string) $mobileUrl);
        if ($mobileUrl === '' || !UrlGuard::isSafeMedia($mobileUrl)) {
            return '';
        }

        // Граница та же, что у мобильной раскладки блоков: 720px.
        $media = '(max-width: 720px)';
        $sizesAttr = ' sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '"';
        $html = '';

        $variants = self::webpVariants($mobileUrl);
        $srcset = $variants !== null ? self::webpSrcset($variants) : [];
        if ($srcset !== []) {
            $html .= '<source media="' . $media . '" type="image/webp" srcset="'
                . implode(', ', $srcset) . '"' . $sizesAttr . '>';
        }

        return $html . '<source media="' . $media . '" srcset="'
            . htmlspecialchars($mobileUrl, ENT_QUOTES) . '">';
    }

    /**
     * Ранний preload использует тот же responsive WebP-набор, что и <picture>,
     * поэтому браузер не скачивает полноразмерный JPEG параллельно с WebP.
     */
    /**
     * Значение для `background-image`: WebP-вариант с откатом на исходный файл.
     *
     * Фон секции не грузится лениво и не умеет `srcset`, поэтому единственный
     * способ не отдавать тяжёлый JPEG — `image-set()`: браузер выберет WebP,
     * а старый возьмёт исходник из второго источника.
     */
    public static function cssImageSet(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !UrlGuard::isSafeMedia($url)) {
            return '';
        }

        $escape = static fn (string $path): string => str_replace(['\\', '"'], ['\\\\', '\\"'], $path);
        // CDN-префикс дописывает Asset::rewriteMedia() уже по готовой странице,
        // как и для <img>: здесь остаётся обычный путь.
        $original = 'url("' . $escape($url) . '")';

        $variants = self::webpVariants($url);
        $webp = $variants['w1600'] ?? $variants['full'] ?? null;
        if ($webp === null) {
            return $original;
        }

        return 'image-set(url("' . $escape($webp) . '") type("image/webp"), '
            . $original . ' type("image/jpeg"))';
    }

    public static function preloadLink(string $url, string $sizes = '100vw'): string
    {
        $url = trim($url);
        if ($url === '' || !UrlGuard::isSafeMedia($url)) {
            return '';
        }

        $href = $url;
        $responsive = '';
        $variants = self::webpVariants($url);
        if ($variants !== null) {
            $srcset = self::webpSrcset($variants);
            if ($srcset !== []) {
                $href = $variants['full'] ?? $variants['w1600'] ?? $variants['w800'] ?? $url;
                $responsive = ' type="image/webp" imagesrcset="' . implode(', ', $srcset)
                    . '" imagesizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '"';
            }
        }

        return '<link rel="preload" as="image" href="' . htmlspecialchars($href, ENT_QUOTES) . '"'
            . $responsive . ' fetchpriority="high">';
    }

    /** @param array{full: ?string, w1600: ?string, w800: ?string} $variants */
    private static function webpSrcset(array $variants): array
    {
        $srcset = [];
        if ($variants['w800'] !== null) {
            $srcset[] = htmlspecialchars($variants['w800'], ENT_QUOTES) . ' '
                . self::imageWidth($variants['w800'], 800) . 'w';
        }
        if ($variants['w1600'] !== null) {
            $srcset[] = htmlspecialchars($variants['w1600'], ENT_QUOTES) . ' '
                . self::imageWidth($variants['w1600'], 1600) . 'w';
        }
        if ($variants['full'] !== null) {
            $srcset[] = htmlspecialchars($variants['full'], ENT_QUOTES) . ' '
                . self::imageWidth($variants['full'], 2000) . 'w';
        }

        return $srcset;
    }

    /**
     * Возвращает пути к существующим WebP-вариантам для локального URL загрузки,
     * либо null, если это не локальная загрузка / вариантов нет.
     *
     * @return array{full: ?string, w1600: ?string, w800: ?string}|null
     */
    private static function webpVariants(string $url): ?array
    {
        if (array_key_exists($url, self::$variantCache)) {
            return self::$variantCache[$url];
        }

        $urlPrefix = rtrim((string) Config::get('paths.public_uploads_url', '/uploads/public'), '/');
        $diskBase = rtrim((string) Config::get('paths.public_uploads', ''), '/');
        if ($diskBase === '' || !str_starts_with($url, $urlPrefix . '/')) {
            return self::$variantCache[$url] = null;
        }

        // Отбрасываем querystring/anchor.
        $clean = preg_replace('/[?#].*$/', '', $url) ?? $url;
        $relative = substr($clean, strlen($urlPrefix));           // /abc.jpg
        $relNoExt = preg_replace('/\.[^.\/]+$/', '', $relative) ?? $relative;

        $map = [
            'full' => $relNoExt . '.webp',
            'w1600' => $relNoExt . '-1600.webp',
            'w800' => $relNoExt . '-800.webp',
        ];

        $result = ['full' => null, 'w1600' => null, 'w800' => null];
        $found = false;
        foreach ($map as $key => $rel) {
            if (is_file($diskBase . $rel)) {
                $result[$key] = $urlPrefix . $rel;
                $found = true;
            }
        }

        return self::$variantCache[$url] = ($found ? $result : null);
    }

    /** @return array{width: int, height: int}|null */
    private static function imageDimensions(string $url): ?array
    {
        if (array_key_exists($url, self::$dimensionCache)) {
            return self::$dimensionCache[$url];
        }

        $path = self::localUploadPath($url);
        if ($path === null) {
            return self::$dimensionCache[$url] = null;
        }

        $size = @getimagesize($path);
        if ($size === false || (int) $size[0] < 1 || (int) $size[1] < 1) {
            return self::$dimensionCache[$url] = null;
        }

        return self::$dimensionCache[$url] = ['width' => (int) $size[0], 'height' => (int) $size[1]];
    }

    private static function imageWidth(string $url, int $fallback): int
    {
        return self::imageDimensions($url)['width'] ?? $fallback;
    }

    private static function localUploadPath(string $url): ?string
    {
        $urlPrefix = rtrim((string) Config::get('paths.public_uploads_url', '/uploads/public'), '/');
        $diskBase = rtrim((string) Config::get('paths.public_uploads', ''), '/');
        $clean = preg_replace('/[?#].*$/', '', $url) ?? $url;

        if ($diskBase === '' || !str_starts_with($clean, $urlPrefix . '/')) {
            return null;
        }

        $relative = substr($clean, strlen($urlPrefix));
        if ($relative === '' || str_contains(str_replace('\\', '/', $relative), '/../')) {
            return null;
        }

        $path = $diskBase . $relative;

        return is_file($path) ? $path : null;
    }

    /** Ниже этого размера картинка считается заглушкой, а не изображением. */
    private const MIN_DIMENSION = 8;

    /**
     * Собственные размеры локального изображения с кэшем по пути и mtime.
     * Внешние адреса (CDN, чужие домены) пропускаем: файла рядом нет.
     *
     * @return array{0: int, 1: int}|null
     */
    public static function dimensions(string $url): ?array
    {
        if ($url === '' || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }
        $path = \dirname(__DIR__, 2) . '/public' . explode('?', $url)[0];
        if (!is_file($path)) {
            return null;
        }
        // SVG размеров в пикселях может не иметь — атрибуты только навредят.
        if (strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
            return null;
        }

        $key = 'media:dim:' . md5($path . ':' . (string) filemtime($path));
        $cached = Cache::remember($key, static function () use ($path): array {
            $size = @getimagesize($path);
            if (!is_array($size)) {
                return [];
            }
            [$width, $height] = [(int) $size[0], (int) $size[1]];

            // Пиксельные заглушки (1×1 и прочая мелочь) размерами не
            // описываются: их ставят как плейсхолдер, и атрибуты только
            // навязали бы CSS-компоненту бессмысленные пропорции.
            return $width >= self::MIN_DIMENSION && $height >= self::MIN_DIMENSION
                ? [$width, $height]
                : [];
        }, 86400);

        return is_array($cached) && count($cached) === 2 ? [(int) $cached[0], (int) $cached[1]] : null;
    }
}
