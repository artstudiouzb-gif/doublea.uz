<?php

use App\Core\Asset;
use App\Core\Locale;
use App\Models\MenuItem;

/** @var string $siteName */
/** @var string $logo */
/** @var array<int, array<string, mixed>> $menuItems */
/** @var array<int, array<string, mixed>> $languages */
/** @var string $metaTitle */
/** @var string $metaDescription */

$currentLang = Locale::current();
$metaTitle = trim($metaTitle) !== '' ? $metaTitle : $siteName;
$metaDescription = trim($metaDescription);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= htmlspecialchars($metaTitle, ENT_QUOTES) ?></title>
    <?php if ($metaDescription !== ''): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(Asset::url('/assets/css/frontend-v2.css'), ENT_QUOTES) ?>">
    <script src="<?= htmlspecialchars(Asset::url('/assets/js/frontend-v2.js'), ENT_QUOTES) ?>" defer></script>
</head>
<body class="aa2-page">
<a class="aa2-skip" href="#main-content"><?= htmlspecialchars(t('Перейти к содержимому'), ENT_QUOTES) ?></a>
<header class="aa2-header" data-aa2-header>
    <div class="aa2-shell aa2-header__inner">
        <a class="aa2-brand" href="<?= htmlspecialchars(Locale::url('/', $currentLang), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
            <?php if ($logo !== ''): ?>
                <img class="aa2-brand__logo" src="<?= htmlspecialchars($logo, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
            <?php else: ?>
                <span class="aa2-brand__wordmark"><?= htmlspecialchars($siteName, ENT_QUOTES) ?></span>
            <?php endif; ?>
        </a>

        <button class="aa2-menu-toggle" type="button" data-aa2-menu-toggle aria-expanded="false" aria-controls="aa2-nav">
            <span><?= htmlspecialchars(t('Меню'), ENT_QUOTES) ?></span>
            <span class="aa2-menu-toggle__icon" aria-hidden="true"></span>
        </button>

        <div class="aa2-header__navwrap" id="aa2-nav" data-aa2-nav>
            <nav class="aa2-nav" aria-label="<?= htmlspecialchars(t('Основное меню'), ENT_QUOTES) ?>">
                <?php foreach ($menuItems as $item): ?>
                    <?php if (!empty($item['is_divider'])) { continue; } ?>
                    <?php
                    $url = MenuItem::resolveUrl($item, $currentLang);
                    $children = array_values(array_filter(
                        $item['children'] ?? [],
                        static fn (array $child): bool => (int) ($child['is_active'] ?? 0) === 1 && empty($child['is_divider'])
                    ));
                    ?>
                    <?php if ($children === []): ?>
                        <a class="aa2-nav__link" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></a>
                    <?php else: ?>
                        <details class="aa2-nav__group">
                            <summary class="aa2-nav__summary"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></summary>
                            <div class="aa2-nav__submenu">
                                <?php foreach ($children as $child): ?>
                                    <a class="aa2-nav__sublink" href="<?= htmlspecialchars(MenuItem::resolveUrl($child, $currentLang), ENT_QUOTES) ?>"><?= htmlspecialchars((string) ($child['title'] ?? ''), ENT_QUOTES) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <?php if (count($languages) > 1): ?>
                <nav class="aa2-langs" aria-label="<?= htmlspecialchars(t('Языки'), ENT_QUOTES) ?>">
                    <?php foreach ($languages as $language): ?>
                        <?php $code = (string) ($language['code'] ?? ''); ?>
                        <?php if ($code === '') { continue; } ?>
                        <a class="aa2-langs__link<?= $code === $currentLang ? ' is-active' : '' ?>" href="<?= htmlspecialchars(Locale::url('/', $code), ENT_QUOTES) ?>"<?= $code === $currentLang ? ' aria-current="page"' : '' ?>><?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</header>
