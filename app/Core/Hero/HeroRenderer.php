<?php

declare(strict_types=1);

namespace App\Core\Hero;

use App\Core\AppUrl;
use App\Core\Icon;
use App\Core\Media;
use App\Core\MediaPosition;
use App\Core\TitleMarkup;
use App\Core\UrlGuard;

/**
 * Разметка обложки: слои фона, контент, навигация.
 *
 * Порядок слоёв фиксирован и держится z-index'ами, а не порядком в потоке:
 *
 *     фон (картинка / видео / YouTube) → затемнение → контент → кнопки
 *     → навигация → индикатор
 *
 * Фон вынут из потока (`position:absolute`) — он не влияет ни на высоту
 * обложки, ни на ширину страницы. Навигация лежит отдельной полосой внизу, а
 * контенту снизу зарезервировано место под неё: перекрывать текст и кнопки
 * стрелками нельзя.
 *
 * Картинка-замена (`hero__fallback`) есть у КАЖДОГО слайда с видео и лежит под
 * ним всегда. Поэтому «видео не загрузилось», «автовоспроизведение запрещено»,
 * «видео выключено на телефоне» и «включено меньше движения» — это не четыре
 * разных сценария с отдельной обработкой, а один: скрыть видео, под ним уже
 * готовый кадр. Пустой обложки не бывает.
 */
final class HeroRenderer
{
    /**
     * Отступ сверху у частей текстового блока: ключ настройки => переменная.
     * Список один на обложку и на слайд — иначе переопределение слайда
     * молча разъедется с общей настройкой.
     */
    private const GAP_VARS = [
        'gap_title' => '--hero-gap-title',
        'gap_subtitle' => '--hero-gap-subtitle',
        'gap_actions' => '--hero-gap-actions',
        'gap_art' => '--hero-gap-art',
    ];

    /** Готовые цветовые схемы: фон и цвет собственного текста обложки. */
    private const SCHEME_COLORS = [
        'light' => ['bg' => '#f2f5f9', 'fg' => '#101a2b'],
        'dark' => ['bg' => '#0b1220', 'fg' => '#ffffff'],
        'navy' => ['bg' => 'var(--gov-navy, #173a63)', 'fg' => '#ffffff'],
    ];

    private const OVERLAY_ANGLES = [
        'to_right' => '90deg',
        'to_left' => '270deg',
        'to_bottom' => '180deg',
        'to_top' => '0deg',
        'to_bottom_right' => '135deg',
        'to_bottom_left' => '225deg',
        'to_top_right' => '45deg',
        'to_top_left' => '315deg',
    ];

    /** Высоты секции: пресет → значение min-height. */
    private const HEIGHTS = [
        'compact' => 'clamp(280px, 32vw, 420px)',
        'regular' => 'clamp(420px, 46vw, 620px)',
        'tall' => 'clamp(520px, 60vw, 780px)',
        'full' => '100dvh',
    ];

    private const HEIGHTS_MOBILE = [
        'compact' => 'clamp(220px, 52vw, 320px)',
        'regular' => 'clamp(320px, 76vw, 460px)',
        'tall' => 'clamp(420px, 96vw, 560px)',
        'full' => '100dvh',
    ];

    /**
     * @param array<string, mixed> $hero строка heroes
     * @param array<int, array<string, mixed>> $slides строки hero_slides (уже локализованные)
     * @param array<string, mixed> $settings общие настройки обложки
     * @return array{html: string, css: string, preload: string}
     */
    public static function render(array $hero, array $slides, array $settings, int $blockId, string $headingTag = 'h1'): array
    {
        $slides = array_values(array_filter(
            $slides,
            static fn (array $slide): bool => !HeroSlideData::isEmpty($slide['data'])
        ));
        if ($slides === []) {
            return ['html' => '', 'css' => '', 'preload' => ''];
        }

        $scope = '#block-' . $blockId;
        $count = count($slides);
        $css = self::rootCss($scope, $settings);
        $slidesHtml = '';
        $preload = '';

        foreach ($slides as $index => $slide) {
            $data = $slide['data'];
            [$slideHtml, $slideCss] = self::slide(
                $data,
                $index,
                $count,
                $settings,
                $scope,
                $index === 0 ? $headingTag : 'h2'
            );
            $slidesHtml .= $slideHtml;
            if ($slideCss !== '') {
                $css .= "\n" . $slideCss;
            }
            if ($index === 0) {
                // Один кандидат в LCP: первый кадр обложки. Остальные слайды
                // грузятся лениво — предзагружать их значило бы конкурировать
                // с CSS и шрифтами первого экрана.
                $preload = HeroSlideData::fallbackImage($data);
            }
        }

        $label = trim((string) ($hero['name'] ?? ''));
        // В aria-label уходит текст без звёздочек: диктору не нужна разметка.
        $firstTitle = trim(TitleMarkup::plain((string) ($slides[0]['data']['title'] ?? '')));
        if ($firstTitle !== '') {
            $label = $firstTitle;
        }

        $html = '<div class="' . self::rootClasses($settings, $count) . '"'
            . self::rootAttributes($settings, $count, $label)
            . '><div class="hero__slides">' . $slidesHtml . '</div>'
            . self::nav($slides, $settings, $count)
            . '</div>';

        return ['html' => $html, 'css' => $css, 'preload' => $preload];
    }

    /** @param array<string, mixed> $s */
    private static function rootClasses(array $s, int $count): string
    {
        $classes = [
            'hero',
            'hero--w-' . $s['width'],
            'hero--h-' . $s['height'],
            'hero--scheme-' . $s['scheme'],
            'hero--tr-' . str_replace('_', '-', (string) $s['transition']),
            'hero--nav-' . str_replace('_', '-', (string) $s['nav_indicator']),
        ];
        if ($count > 1) {
            $classes[] = 'hero--carousel';
        }
        if ($s['height_mobile'] !== '') {
            $classes[] = 'hero--hm-' . $s['height_mobile'];
        }
        if (!$s['nav_arrows']) {
            $classes[] = 'hero--no-arrows';
        }
        if (!$s['nav_arrows_mobile']) {
            $classes[] = 'hero--no-arrows-mobile';
        }

        return implode(' ', $classes);
    }

    /** @param array<string, mixed> $s */
    private static function rootAttributes(array $s, int $count, string $label): string
    {
        $attrs = [
            'data-hero',
            'data-hero-transition="' . htmlspecialchars((string) $s['transition'], ENT_QUOTES) . '"',
            'data-hero-duration="' . (int) $s['transition_duration'] . '"',
            'data-hero-count="' . $count . '"',
        ];

        if ($count > 1) {
            $attrs[] = 'role="region"';
            $attrs[] = 'aria-roledescription="' . htmlspecialchars(t('Карусель'), ENT_QUOTES) . '"';
            $attrs[] = 'aria-label="' . htmlspecialchars($label !== '' ? $label : t('Обложка'), ENT_QUOTES) . '"';
            // Фокус на самой карусели — точка входа для стрелок клавиатуры.
            $attrs[] = 'tabindex="0"';
            if ($s['nav_swipe']) {
                $attrs[] = 'data-hero-swipe';
            }
            if ($s['autoplay']) {
                $attrs[] = 'data-hero-autoplay="' . ((int) $s['autoplay_interval'] * 1000) . '"';
                if ($s['autoplay_pause_hover']) {
                    $attrs[] = 'data-hero-pause-hover';
                }
                if ($s['autoplay_pause_interaction']) {
                    $attrs[] = 'data-hero-pause-interaction';
                }
                if ($s['autoplay_resume']) {
                    $attrs[] = 'data-hero-resume="' . ((int) $s['autoplay_resume_delay'] * 1000) . '"';
                }
                if ($s['autoplay_mobile']) {
                    $attrs[] = 'data-hero-autoplay-mobile';
                }
            }
        }

        return ' ' . implode(' ', $attrs);
    }

    /**
     * Переменные обложки. Всё, что может отличаться от слайда к слайду, здесь
     * задаёт лишь значение по умолчанию — слайд перекрывает его своим блоком.
     *
     * @param array<string, mixed> $s
     */
    private static function rootCss(string $scope, array $s): string
    {
        $vars = self::schemeVars((string) $s['scheme'], (string) $s['scheme_bg'], (string) $s['scheme_text'], (string) $s['scheme_accent']);
        $vars['--hero-overlay'] = self::overlayValue(
            (string) $s['overlay'],
            (string) $s['overlay_color'],
            (int) $s['overlay_opacity'],
            (string) $s['overlay_direction'],
            (string) $s['text_position']
        );
        $vars['--hero-panel-bg'] = $s['panel']
            ? 'rgba(' . self::rgb((string) $s['panel_color']) . ',' . self::alpha((int) $s['panel_opacity']) . ')'
            : 'transparent';
        $vars['--hero-duration'] = (int) $s['transition_duration'] . 'ms';
        $vars['--hero-title-size'] = 'var(--hero-title-' . $s['title_size'] . ')';
        $vars['--hero-subtitle-size'] = 'var(--hero-subtitle-' . $s['subtitle_size'] . ')';
        foreach (self::GAP_VARS as $key => $var) {
            $vars[$var] = (int) $s[$key] . 'px';
        }

        if ($s['height'] === 'custom' && $s['height_value'] !== '') {
            $vars['--hero-min-h'] = (string) $s['height_value'];
        } elseif (isset(self::HEIGHTS[$s['height']])) {
            $vars['--hero-min-h'] = self::HEIGHTS[$s['height']];
        }
        if ($s['text_width'] !== '') {
            $vars['--hero-text-width'] = (string) $s['text_width'];
        }

        $css = $scope . ' .hero{' . self::declarations($vars) . '}';

        // Телефон: своя высота и свои размеры текста. Десктопная композиция на
        // узком экране разъезжается, и общей настройкой это лечится только
        // ценой десктопа.
        $mobile = [];
        if ($s['height_mobile'] === 'custom' && $s['height_mobile_value'] !== '') {
            $mobile['--hero-min-h'] = (string) $s['height_mobile_value'];
        } elseif ($s['height_mobile'] !== '' && isset(self::HEIGHTS_MOBILE[$s['height_mobile']])) {
            $mobile['--hero-min-h'] = self::HEIGHTS_MOBILE[$s['height_mobile']];
        }
        if ($s['title_size_mobile'] !== '') {
            $mobile['--hero-title-size'] = 'var(--hero-title-' . $s['title_size_mobile'] . ')';
        }
        if ($s['subtitle_size_mobile'] !== '') {
            $mobile['--hero-subtitle-size'] = 'var(--hero-subtitle-' . $s['subtitle_size_mobile'] . ')';
        }
        if ($mobile !== []) {
            $css .= "\n@media (max-width:720px){" . $scope . ' .hero{' . self::declarations($mobile) . '}}';
        }

        return $css;
    }

    /**
     * Один слайд: фон, затемнение, контент, кнопки.
     *
     * @param array<string, mixed> $d
     * @param array<string, mixed> $s
     * @return array{string, string}
     */
    private static function slide(array $d, int $index, int $count, array $s, string $scope, string $headingTag): array
    {
        $first = $index === 0;
        $position = $d['text_position'] !== '' ? $d['text_position'] : $s['text_position'];
        $alignY = $d['text_align_y'] !== '' ? $d['text_align_y'] : $s['text_align_y'];
        $panel = $d['panel'] !== '' ? $d['panel'] === 'on' : (bool) $s['panel'];
        $contentScheme = self::contentScheme($d, $s);

        $classes = [
            'hero__slide',
            'hero--pos-' . $position,
            'hero--y-' . $alignY,
            'hero--content-' . $contentScheme,
        ];
        if ($first) {
            $classes[] = 'is-active';
        }
        if ($panel) {
            $classes[] = 'hero__slide--panel';
        }
        // Свой класс редактора идёт последним: по нему пишут стили в «Свой
        // CSS» страницы, и он должен выигрывать у классов раскладки.
        if ((string) $d['css_class'] !== '') {
            $classes[] = (string) $d['css_class'];
        }
        if ($d['text_position_mobile'] !== '') {
            $classes[] = 'hero--pos-m-' . $d['text_position_mobile'];
        } elseif ($s['text_position_mobile'] !== '') {
            $classes[] = 'hero--pos-m-' . $s['text_position_mobile'];
        }
        if ($d['text_align_y_mobile'] !== '') {
            $classes[] = 'hero--y-m-' . $d['text_align_y_mobile'];
        } elseif ($s['text_align_y_mobile'] !== '') {
            $classes[] = 'hero--y-m-' . $s['text_align_y_mobile'];
        }

        $html = '<div class="' . implode(' ', $classes) . '" data-hero-slide data-hero-index="' . $index . '"'
            // Своя длительность показа. Атрибута нет — слайд держится столько
            // же, сколько остальные (интервал обложки).
            . ((int) $d['duration'] > 0 ? ' data-hero-slide-duration="' . ((int) $d['duration'] * 1000) . '"' : '')
            . ' role="group" aria-roledescription="' . htmlspecialchars(t('Слайд'), ENT_QUOTES) . '"'
            . ' aria-label="' . ($index + 1) . ' ' . htmlspecialchars(t('из'), ENT_QUOTES) . ' ' . $count . '"'
            . ' aria-hidden="' . ($first ? 'false' : 'true') . '"'
            . ($first ? '' : ' inert')
            . '>'
            . self::media($d, $first)
            . self::overlay($d, $s)
            . self::cover($d)
            . self::watermark($d)
            . '<div class="hero__inner"><div class="hero__text">'
            . self::art($d)
            . self::text($d, $headingTag)
            . self::actions($d)
            . '</div></div>'
            . '</div>';

        return [$html, self::slideCss($d, $s, $scope, $index, $position)];
    }

    /**
     * Слой фона. Картинка-замена рендерится всегда и лежит под видео: это и
     * постер, и то, что остаётся, если видео показать нельзя.
     *
     * @param array<string, mixed> $d
     */
    private static function media(array $d, bool $first): string
    {
        $type = (string) $d['media_type'];
        if ($type === 'none') {
            return '';
        }

        $positions = MediaPosition::classes($d['image_position'], $d['image_position_mobile']);
        $fallback = HeroSlideData::fallbackImage($d);
        $layers = '';

        if ($fallback !== '') {
            $layers .= Media::picture(
                $fallback,
                '',
                null,
                null,
                'hero__image ' . $positions,
                !$first,
                '100vw',
                $first,
                'hero__fallback ' . $positions,
                $d['image_mobile'] !== '' ? (string) $d['image_mobile'] : null
            );
        }

        if ($type === 'video' && $d['video_url'] !== '') {
            $layers .= self::video($d, $first);
        } elseif ($type === 'youtube' && $d['youtube_id'] !== '') {
            $layers .= self::youtube($d);
        }

        return $layers === '' ? '' : '<div class="hero__media" aria-hidden="true">' . $layers . '</div>';
    }

    /** @param array<string, mixed> $d */
    private static function video(array $d, bool $first): string
    {
        $poster = HeroSlideData::fallbackImage($d);
        $sources = '<source src="' . htmlspecialchars((string) $d['video_url'], ENT_QUOTES) . '" type="video/mp4">';

        // Ролик не стартует по атрибуту autoplay: старт даёт скрипт, когда слайд
        // действительно показан. Иначе карусель тянула бы все видео сразу — на
        // мобильном трафике это дороже, чем всё остальное на странице.
        return '<video class="hero__video" data-hero-video'
            . ' data-hero-mobile-media="' . htmlspecialchars((string) $d['mobile_media'], ENT_QUOTES) . '"'
            . ($d['video_mobile_url'] !== ''
                ? ' data-hero-video-mobile="' . htmlspecialchars((string) $d['video_mobile_url'], ENT_QUOTES) . '"'
                : '')
            . ($first ? ' preload="metadata"' : ' preload="none"')
            . ($poster !== '' ? ' poster="' . htmlspecialchars($poster, ENT_QUOTES) . '"' : '')
            . ' muted loop playsinline webkit-playsinline'
            . ' disablepictureinpicture disableremoteplayback'
            . ' controlslist="nodownload nofullscreen noremoteplayback noplaybackrate"'
            . ' tabindex="-1" aria-hidden="true" hidden>' . $sources . '</video>';
    }

    /**
     * YouTube как фон. Iframe создаёт скрипт: до его готовности (и навсегда,
     * если ролик недоступен или автовоспроизведение запрещено) виден постер.
     *
     * @param array<string, mixed> $d
     */
    private static function youtube(array $d): string
    {
        $params = [
            'autoplay' => '1',
            'mute' => '1',
            'loop' => '1',
            'playlist' => (string) $d['youtube_id'],
            'controls' => '0',
            'playsinline' => '1',
            'disablekb' => '1',
            'fs' => '0',
            'modestbranding' => '1',
            'rel' => '0',
            'iv_load_policy' => '3',
            'enablejsapi' => '1',
        ];
        $origin = AppUrl::base();
        if ($origin !== '') {
            $params['origin'] = $origin;
        }
        $src = 'https://www.youtube-nocookie.com/embed/' . rawurlencode((string) $d['youtube_id'])
            . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return '<div class="hero__yt" data-hero-youtube'
            . ' data-hero-mobile-media="' . htmlspecialchars((string) $d['mobile_media'], ENT_QUOTES) . '"'
            . ' data-hero-yt-src="' . htmlspecialchars($src, ENT_QUOTES) . '" aria-hidden="true"></div>';
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, mixed> $s
     */
    private static function overlay(array $d, array $s): string
    {
        $mode = $d['overlay'] !== '' ? (string) $d['overlay'] : (string) $s['overlay'];

        return $mode === 'none' ? '' : '<div class="hero__overlay" aria-hidden="true"></div>';
    }

    /**
     * Ссылка-подложка: кликается весь слайд, но кнопки остаются отдельными
     * ссылками, а не вложенными в чужую (такая вложенность недопустима в HTML
     * и ломает переход по Tab).
     *
     * @param array<string, mixed> $d
     */
    private static function cover(array $d): string
    {
        $url = (string) $d['link_url'];
        if ($url === '' || !UrlGuard::isSafeLink($url)) {
            return '';
        }

        return '<a class="hero__cover" href="' . htmlspecialchars($url, ENT_QUOTES) . '"'
            . ($d['link_new_tab'] ? ' target="_blank" rel="noopener"' : '')
            . ' tabindex="-1" aria-hidden="true"></a>';
    }

    /** @param array<string, mixed> $d */
    private static function art(array $d): string
    {
        $image = (string) $d['art_image'];
        if ($image === '' || !UrlGuard::isSafeMedia($image)) {
            return '';
        }
        $alt = (string) $d['art_alt'];
        $heights = ['small' => 64, 'medium' => 120, 'large' => 200];

        // Без описания картинка декоративная и скрыта от диктора; с описанием
        // это содержимое, и прятать его нельзя. Явная высота обязательна: SVG
        // без пиксельных размеров схлопывается (ширина считается из viewBox).
        return '<span class="hero__art hero__art--' . htmlspecialchars((string) $d['art_size'], ENT_QUOTES)
            . ' hero__art--' . htmlspecialchars((string) $d['art_position'], ENT_QUOTES) . '"'
            . ($alt === '' ? ' aria-hidden="true"' : '') . '>'
            . '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"'
            . ' loading="lazy" decoding="async" height="' . ($heights[$d['art_size']] ?? 120) . '"></span>';
    }

    /**
     * Фоновая надпись — крупное слово за контентом.
     *
     * Лежит между фоном и текстом и ничего не перехватывает: `aria-hidden`
     * убирает её у диктора (иначе он читает её посреди заголовка), а
     * `pointer-events: none` в CSS — у мыши, иначе слово шириной в экран
     * накрыло бы кнопки.
     *
     * @param array<string, mixed> $d
     */
    private static function watermark(array $d): string
    {
        $text = trim((string) $d['watermark']);
        if ($text === '') {
            return '';
        }

        return '<span class="hero__watermark hero__watermark--x-' . htmlspecialchars((string) $d['watermark_x'], ENT_QUOTES)
            . ' hero__watermark--y-' . htmlspecialchars((string) $d['watermark_y'], ENT_QUOTES)
            . ' hero__watermark--' . htmlspecialchars((string) $d['watermark_style'], ENT_QUOTES)
            . ' hero__watermark--font-' . htmlspecialchars((string) $d['watermark_font'], ENT_QUOTES) . '"'
            . ' aria-hidden="true">' . htmlspecialchars($text, ENT_QUOTES) . '</span>';
    }

    /** @param array<string, mixed> $d */
    private static function text(array $d, string $headingTag): string
    {
        $html = '';
        if ($d['eyebrow'] !== '') {
            $html .= '<span class="hero__eyebrow">' . htmlspecialchars((string) $d['eyebrow'], ENT_QUOTES) . '</span>';
        }
        if ($d['title'] !== '') {
            $title = TitleMarkup::html((string) $d['title']);
            $url = (string) $d['link_url'];
            if ($url !== '' && UrlGuard::isSafeLink($url)) {
                $title = '<a class="hero__title-link" href="' . htmlspecialchars($url, ENT_QUOTES) . '"'
                    . ($d['link_new_tab'] ? ' target="_blank" rel="noopener"' : '') . '>' . $title . '</a>';
            }
            $html .= '<' . $headingTag . ' class="hero__title">' . $title . '</' . $headingTag . '>';
        }
        if ($d['subtitle'] !== '') {
            $html .= '<p class="hero__subtitle">' . nl2br(htmlspecialchars((string) $d['subtitle'], ENT_QUOTES)) . '</p>';
        }

        return $html;
    }

    /** @param array<string, mixed> $d */
    private static function actions(array $d): string
    {
        $buttons = '';
        foreach (['cta', 'cta2'] as $key) {
            $buttons .= self::button($d, $key);
        }

        return $buttons === '' ? '' : '<div class="hero__actions">' . $buttons . '</div>';
    }

    /** @param array<string, mixed> $d */
    private static function button(array $d, string $key): string
    {
        if (empty($d[$key . '_enabled'])) {
            return '';
        }
        $text = trim((string) $d[$key . '_text']);
        $url = (string) $d[$key . '_url'];
        if ($text === '' || $url === '' || !UrlGuard::isSafeLink($url)) {
            return '';
        }

        // Своя картинка побеждает иконку из набора: её ставят ради знака,
        // которого в Tabler нет, и молча подменять его иконкой нельзя.
        // Цвет у картинки собственный — под кнопку она не перекрашивается,
        // в отличие от <use>, который берёт currentColor.
        $image = trim((string) ($d[$key . '_image'] ?? ''));
        $icon = (string) $d[$key . '_icon'];
        if ($image !== '' && UrlGuard::isSafeMedia($image)) {
            $iconHtml = '<span class="hero__cta-icon" aria-hidden="true">'
                . '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt=""'
                . ' width="20" height="20" loading="lazy" decoding="async"></span>';
        } elseif ($icon !== '') {
            $iconHtml = '<span class="hero__cta-icon" aria-hidden="true">' . Icon::render($icon, 20) . '</span>';
        } else {
            $iconHtml = '';
        }

        return '<a class="hero__cta hero__cta--' . htmlspecialchars((string) $d[$key . '_style'], ENT_QUOTES)
            . ($iconHtml !== '' ? ' hero__cta--with-icon' : '') . '"'
            . ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"'
            . (!empty($d[$key . '_new_tab']) ? ' target="_blank" rel="noopener"' : '')
            . '>' . $iconHtml . '<span>' . htmlspecialchars($text, ENT_QUOTES) . '</span></a>';
    }

    /**
     * Переменные конкретного слайда: они нужны только там, где слайд отходит
     * от настроек обложки.
     *
     * @param array<string, mixed> $d
     * @param array<string, mixed> $s
     */
    private static function slideCss(array $d, array $s, string $scope, int $index, string $position): string
    {
        $vars = [];
        if ($d['scheme'] !== '') {
            $vars = self::schemeVars(
                (string) $d['scheme'],
                $d['scheme_bg'] !== '' ? (string) $d['scheme_bg'] : (string) $s['scheme_bg'],
                $d['scheme_text'] !== '' ? (string) $d['scheme_text'] : (string) $s['scheme_text'],
                $d['scheme_accent'] !== '' ? (string) $d['scheme_accent'] : (string) $s['scheme_accent']
            );
        }

        $overlayMode = $d['overlay'] !== '' ? (string) $d['overlay'] : (string) $s['overlay'];
        if ($overlayMode !== 'none'
            && ($d['overlay'] !== '' || $d['overlay_color'] !== '' || $d['overlay_opacity'] >= 0 || $d['overlay_direction'] !== '')
        ) {
            $vars['--hero-overlay'] = self::overlayValue(
                $overlayMode,
                $d['overlay_color'] !== '' ? (string) $d['overlay_color'] : (string) $s['overlay_color'],
                $d['overlay_opacity'] >= 0 ? (int) $d['overlay_opacity'] : (int) $s['overlay_opacity'],
                $d['overlay_direction'] !== '' ? (string) $d['overlay_direction'] : (string) $s['overlay_direction'],
                $position
            );
        }
        if ($d['image_fit'] === 'contain') {
            $vars['--hero-fit'] = 'contain';
        }
        // Отступы частей текста: пусто у слайда — берётся значение обложки,
        // поэтому переменную объявляем только когда слайд от неё отходит.
        foreach (self::GAP_VARS as $key => $var) {
            if ($d[$key] !== '') {
                $vars[$var] = (int) $d[$key] . 'px';
            }
        }

        // Прозрачность фоновой надписи — своя у слайда, поэтому переменной в
        // scoped CSS: инлайн-стили в блоках запрещены.
        if (trim((string) $d['watermark']) !== '') {
            $vars['--hero-watermark-opacity'] = (string) round(((int) $d['watermark_opacity']) / 100, 3);
            $vars['--hero-watermark-size'] = (int) $d['watermark_size'] . 'vw';
            $vars['--hero-watermark-dx'] = (int) $d['watermark_dx'] . '%';
            $vars['--hero-watermark-dy'] = (int) $d['watermark_dy'] . '%';
            $vars['--hero-watermark-stroke'] = (int) $d['watermark_stroke'] . 'px';
            if ((string) $d['watermark_color'] !== '') {
                $vars['--hero-watermark-ink'] = (string) $d['watermark_color'];
            }
        }

        $desktop = [];
        if ($d['title_size'] !== '') {
            $desktop['--hero-title-size'] = 'var(--hero-title-' . $d['title_size'] . ')';
        }
        if ($d['subtitle_size'] !== '') {
            $desktop['--hero-subtitle-size'] = 'var(--hero-subtitle-' . $d['subtitle_size'] . ')';
        }
        $vars = array_merge($vars, $desktop);

        $selector = $scope . ' .hero__slide[data-hero-index="' . $index . '"]';
        $css = $vars === [] ? '' : $selector . '{' . self::declarations($vars) . '}';

        $mobile = [];
        if ($d['title_size_mobile'] !== '') {
            $mobile['--hero-title-size'] = 'var(--hero-title-' . $d['title_size_mobile'] . ')';
        }
        if ($d['subtitle_size_mobile'] !== '') {
            $mobile['--hero-subtitle-size'] = 'var(--hero-subtitle-' . $d['subtitle_size_mobile'] . ')';
        }
        if ($mobile !== []) {
            $css .= ($css !== '' ? "\n" : '')
                . '@media (max-width:720px){' . $selector . '{' . self::declarations($mobile) . '}}';
        }

        return $css;
    }

    /**
     * Навигация: стрелки, индикатор, пауза автопрокрутки. Живёт отдельной
     * полосой и не накрывает контент — под неё в `.hero__inner` зарезервирован
     * нижний отступ.
     *
     * @param array<int, array<string, mixed>> $slides
     * @param array<string, mixed> $s
     */
    private static function nav(array $slides, array $s, int $count): string
    {
        if ($count < 2) {
            return '';
        }

        $indicator = (string) $s['nav_indicator'];
        $arrows = (bool) $s['nav_arrows'];
        if (!$arrows && $indicator === 'none' && !$s['autoplay']) {
            return '';
        }

        $prev = '<button type="button" class="hero__arrow hero__arrow--prev" data-hero-prev'
            . ' aria-label="' . htmlspecialchars(t('Предыдущий слайд'), ENT_QUOTES) . '">'
            . Icon::render('chevron-left', 22) . '</button>';
        $next = '<button type="button" class="hero__arrow hero__arrow--next" data-hero-next'
            . ' aria-label="' . htmlspecialchars(t('Следующий слайд'), ENT_QUOTES) . '">'
            . Icon::render('chevron-right', 22) . '</button>';

        // Внешний слой держит полосу на месте (ширина колонки сайта, отступ от
        // низа), внутренний — сама капсула, которая обжимает содержимое.
        // Двумя элементами, а не одним: капсуле нельзя задать и «во всю
        // ширину контейнера», и «по содержимому» одновременно.
        $html = '<div class="hero__nav"><div class="hero__nav-bar">';
        if ($arrows) {
            $html .= $prev;
        }
        $html .= '<div class="hero__indicator">' . self::indicator($slides, $indicator, $count) . '</div>';
        if ($arrows) {
            $html .= $next;
        }
        if ($s['autoplay']) {
            // Остановить автопрокрутку должен уметь любой посетитель, а не
            // только тот, кто доведёт курсор до слайда (WCAG 2.2.2).
            $html .= '<button type="button" class="hero__playpause" data-hero-toggle'
                . ' aria-label="' . htmlspecialchars(t('Остановить автопрокрутку'), ENT_QUOTES) . '"'
                . ' data-label-play="' . htmlspecialchars(t('Запустить автопрокрутку'), ENT_QUOTES) . '"'
                . ' data-label-pause="' . htmlspecialchars(t('Остановить автопрокрутку'), ENT_QUOTES) . '">'
                . '<span class="hero__playpause-icon hero__playpause-icon--pause" aria-hidden="true">'
                . Icon::render('player-pause', 18) . '</span>'
                . '<span class="hero__playpause-icon hero__playpause-icon--play" aria-hidden="true">'
                . Icon::render('player-play', 18) . '</span></button>';
        }
        $html .= '</div></div>'
            . '<span class="visually-hidden" data-hero-status aria-live="polite"></span>';

        return $html;
    }

    /**
     * @param array<int, array<string, mixed>> $slides
     */
    private static function indicator(array $slides, string $type, int $count): string
    {
        $total = str_pad((string) $count, 2, '0', STR_PAD_LEFT);

        $counter = '<span class="hero__counter" aria-hidden="true">'
            . '<span class="hero__counter-current" data-hero-current>01</span>'
            . '<span class="hero__counter-sep">/</span>'
            . '<span class="hero__counter-total">' . $total . '</span></span>';
        $progress = '<span class="hero__progress" aria-hidden="true">'
            . '<span class="hero__progress-bar" data-hero-progress></span></span>';

        return match ($type) {
            'dots' => self::dots($count),
            'counter' => $counter,
            'progress' => $progress,
            // Полоса идёт перед счётчиком: она показывает, сколько осталось до
            // следующего слайда, а счётчик — где мы сейчас. Слева направо это
            // читается как «время → позиция», а не наоборот.
            'counter_progress' => $progress . $counter,
            'thumbs' => self::thumbs($slides),
            default => '',
        };
    }

    private static function dots(int $count): string
    {
        $html = '<span class="hero__dots" role="group" aria-label="'
            . htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) . '">';
        for ($i = 0; $i < $count; $i++) {
            $html .= '<button type="button" class="hero__dot' . ($i === 0 ? ' is-active' : '') . '"'
                . ' data-hero-goto="' . $i . '"'
                . ' aria-label="' . htmlspecialchars(t('Перейти к слайду'), ENT_QUOTES) . ' ' . ($i + 1) . '"'
                . ' aria-current="' . ($i === 0 ? 'true' : 'false') . '"></button>';
        }

        return $html . '</span>';
    }

    /** @param array<int, array<string, mixed>> $slides */
    private static function thumbs(array $slides): string
    {
        $html = '<span class="hero__thumbs" role="group" aria-label="'
            . htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) . '">';
        foreach ($slides as $i => $slide) {
            $image = HeroSlideData::fallbackImage($slide['data']);
            $title = trim((string) $slide['data']['title']);
            $label = $title !== '' ? $title : t('Перейти к слайду') . ' ' . ($i + 1);
            $html .= '<button type="button" class="hero__thumb' . ($i === 0 ? ' is-active' : '') . '"'
                . ' data-hero-goto="' . $i . '"'
                . ' aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '"'
                . ' aria-current="' . ($i === 0 ? 'true' : 'false') . '">'
                . ($image !== '' && UrlGuard::isSafeMedia($image)
                    ? '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt="" loading="lazy" decoding="async">'
                    : '<span class="hero__thumb-num" aria-hidden="true">' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '</span>')
                . '</button>';
        }

        return $html . '</span>';
    }

    /**
     * Цвет собственного текста обложки.
     *
     * `auto` смотрит на то, что реально окажется под текстом: фотография с
     * затемнением почти всегда тёмная, светлая схема без медиа — светлая.
     * Схема обложки при этом НЕ навязывает цвет вложенным компонентам: у них
     * своя поверхность и свой цвет (см. `.hero__surface` в hero.css).
     *
     * @param array<string, mixed> $d
     * @param array<string, mixed> $s
     */
    private static function contentScheme(array $d, array $s): string
    {
        $mode = $d['content_scheme'] !== '' ? (string) $d['content_scheme'] : (string) $s['content_scheme'];
        if ($mode === 'light' || $mode === 'dark') {
            return $mode;
        }

        $overlay = $d['overlay'] !== '' ? (string) $d['overlay'] : (string) $s['overlay'];
        $opacity = $d['overlay_opacity'] >= 0 ? (int) $d['overlay_opacity'] : (int) $s['overlay_opacity'];
        if (HeroSlideData::hasMedia($d) && $overlay !== 'none' && $opacity >= 25) {
            return 'light';
        }
        if (HeroSlideData::hasMedia($d)) {
            // Фото без заметного затемнения: полагаться на его светлоту нельзя,
            // поэтому берём цвет по схеме — он хотя бы предсказуем.
            return self::schemeIsDark($d, $s) ? 'light' : 'dark';
        }

        return self::schemeIsDark($d, $s) ? 'light' : 'dark';
    }

    /**
     * @param array<string, mixed> $d
     * @param array<string, mixed> $s
     */
    private static function schemeIsDark(array $d, array $s): bool
    {
        $scheme = $d['scheme'] !== '' ? (string) $d['scheme'] : (string) $s['scheme'];
        if ($scheme === 'custom') {
            $bg = $d['scheme_bg'] !== '' ? (string) $d['scheme_bg'] : (string) $s['scheme_bg'];

            return self::luminance($bg) < 0.5;
        }

        return $scheme !== 'light';
    }

    /**
     * @return array<string, string>
     */
    private static function schemeVars(string $scheme, string $bg, string $fg, string $accent): array
    {
        if ($scheme === 'custom') {
            $vars = [
                '--hero-bg' => $bg !== '' ? $bg : '#0b1a30',
                '--hero-fg' => $fg !== '' ? $fg : (self::luminance($bg) < 0.5 ? '#ffffff' : '#101a2b'),
            ];
        } else {
            $preset = self::SCHEME_COLORS[$scheme] ?? self::SCHEME_COLORS['navy'];
            $vars = ['--hero-bg' => $preset['bg'], '--hero-fg' => $preset['fg']];
        }
        // Акцент по умолчанию берётся из настроек «Дизайна»: фирменный цвет
        // задаётся в админке, а не прибивается в коде.
        $vars['--hero-accent'] = $accent !== '' ? $accent : 'var(--color-accent)';

        return $vars;
    }

    /** Готовое значение для `background` слоя затемнения. */
    private static function overlayValue(string $mode, string $color, int $opacity, string $direction, string $textPosition): string
    {
        if ($mode === 'none') {
            return 'transparent';
        }

        $rgb = self::rgb($color);
        $alpha = self::alpha($opacity);
        if ($mode === 'solid') {
            return 'rgba(' . $rgb . ',' . $alpha . ')';
        }

        // «Авто»: градиент уходит от той стороны, где стоит текст, — темнее
        // там, где читают, прозрачнее там, где смотрят на фотографию.
        $angle = self::OVERLAY_ANGLES[$direction]
            ?? ($textPosition === 'right' ? '270deg' : ($textPosition === 'center' ? '0deg' : '90deg'));

        return 'linear-gradient(' . $angle . ', rgba(' . $rgb . ',' . $alpha . ') 0%, rgba(' . $rgb . ','
            . self::alpha((int) round($opacity * 0.82)) . ') 38%, rgba(' . $rgb . ','
            . self::alpha((int) round($opacity * 0.28)) . ') 72%, rgba(' . $rgb . ',0) 100%)';
    }

    private static function rgb(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');
        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            $hex = '0b1a30';
        }

        return (int) hexdec(substr($hex, 0, 2)) . ',' . (int) hexdec(substr($hex, 2, 2)) . ',' . (int) hexdec(substr($hex, 4, 2));
    }

    private static function alpha(int $percent): string
    {
        return rtrim(rtrim(number_format(max(0, min(100, $percent)) / 100, 2, '.', ''), '0'), '.') ?: '0';
    }

    /** Относительная светлота цвета (0 — чёрный, 1 — белый). */
    private static function luminance(string $hex): float
    {
        $hex = ltrim(trim($hex), '#');
        if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
            return 0.0;
        }
        $r = (int) hexdec(substr($hex, 0, 2)) / 255;
        $g = (int) hexdec(substr($hex, 2, 2)) / 255;
        $b = (int) hexdec(substr($hex, 4, 2)) / 255;

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /** @param array<string, string> $vars */
    private static function declarations(array $vars): string
    {
        $out = '';
        foreach ($vars as $name => $value) {
            $out .= $name . ':' . $value . ';';
        }

        return $out;
    }
}
