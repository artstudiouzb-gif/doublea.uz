<?php

use App\Core\Csrf;
use App\Core\Flash;

/** @var string $pageTitle */
/** @var array|null $repoUser */
$repoName = htmlspecialchars((string) \App\Models\Setting::get('site_name', 'Файловый портал'), ENT_QUOTES);
$repoLogo = trim((string) \App\Models\Setting::get('repo_logo', ''));

// Версия для слабовидящих: состояние из cookie (общая с основным сайтом).
$a11ySchemes = ['cw', 'wc', 'bb'];
$a11ySizes = ['m', 'l', 'xl'];
$a11yParts = explode(':', (string) ($_COOKIE['a11y'] ?? ''));
$a11y = [
    'on' => in_array($a11yParts[0] ?? '', $a11ySchemes, true),
    'scheme' => in_array($a11yParts[0] ?? '', $a11ySchemes, true) ? $a11yParts[0] : 'cw',
    'size' => in_array($a11yParts[1] ?? '', $a11ySizes, true) ? $a11yParts[1] : 'm',
    'images' => ($a11yParts[2] ?? '') === 'off' ? 'off' : 'on',
];
?><!doctype html>
<html lang="ru"<?= $a11y['on'] ? ' data-a11y="1" data-a11y-scheme="' . htmlspecialchars($a11y['scheme'], ENT_QUOTES) . '" data-a11y-size="' . htmlspecialchars($a11y['size'], ENT_QUOTES) . '" data-a11y-images="' . htmlspecialchars($a11y['images'], ENT_QUOTES) . '"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <?= \App\Core\Icon::browserConfigHtml() ?>
    <title><?= htmlspecialchars($pageTitle ?? 'Файловый портал', ENT_QUOTES) ?> — <?= $repoName ?></title>
    <link rel="stylesheet" href="/assets/css/noto-sans.css">
    <link rel="stylesheet" href="/assets/css/noto-serif.css">
    <link rel="stylesheet" href="/assets/css/gov-theme.css?v=<?= file_exists(dirname(__DIR__, 3) . '/public/assets/css/gov-theme.css') ? filemtime(dirname(__DIR__, 3) . '/public/assets/css/gov-theme.css') : '2.0.1' ?>">
    <link rel="stylesheet" href="/assets/css/repo.css?v=<?= file_exists(dirname(__DIR__, 3) . '/public/assets/css/repo.css') ? filemtime(dirname(__DIR__, 3) . '/public/assets/css/repo.css') : '2.0.1' ?>">
    <link rel="stylesheet" href="/assets/css/a11y.css">
</head>
<body>
<header class="repo-topbar">
    <a href="/repo" class="repo-topbar__brand">
        <?php if ($repoLogo !== ''): ?>
            <img src="<?= htmlspecialchars($repoLogo, ENT_QUOTES) ?>" alt="<?= $repoName ?>" class="repo-topbar__logo">
        <?php else: ?>
            <?= \App\Core\Icon::render('shield-check', 24) ?>
        <?php endif; ?>
        <span>Защищённое хранилище</span>
    </a>
    <nav>
        <button type="button" class="a11y-toggle" aria-label="Версия для слабовидящих" title="Версия для слабовидящих" aria-controls="a11y-panel" aria-expanded="<?= $a11y['on'] ? 'true' : 'false' ?>">
            <?= \App\Core\Icon::render('eye', 18) ?>
        </button>
        <?php if (!empty($repoUser)): ?>
            <span class="repo-topbar__user"><?= htmlspecialchars((string) ($repoUser['full_name'] ?: $repoUser['username']), ENT_QUOTES) ?></span>
            <a href="/repo">Файлы</a>
            <a href="/repo/security">Безопасность</a>
            <form class="repo-logout-form" method="post" action="/repo/logout">
                <?= Csrf::field() ?>
                <button type="submit">Выйти</button>
            </form>
        <?php endif; ?>
    </nav>
</header>
<?php if ($a11y['on']): ?>
<div class="a11y-panel is-open" id="a11y-panel" role="region" aria-label="Настройки версии для слабовидящих">
    <div class="a11y-panel__group">
        <b>Цвет:</b>
        <button type="button" data-a11y-set="scheme:cw" title="Чёрным по белому">Ч</button>
        <button type="button" data-a11y-set="scheme:wc" title="Белым по чёрному">Б</button>
        <button type="button" data-a11y-set="scheme:bb" title="Тёмно-синим по голубому">С</button>
    </div>
    <div class="a11y-panel__group">
        <b>Размер:</b>
        <button type="button" class="a11y-panel__size-a1" data-a11y-set="size:m" title="Обычный">А</button>
        <button type="button" class="a11y-panel__size-a2" data-a11y-set="size:l" title="Крупный">А</button>
        <button type="button" class="a11y-panel__size-a3" data-a11y-set="size:xl" title="Очень крупный">А</button>
    </div>
    <div class="a11y-panel__group">
        <b>Изображения:</b>
        <button type="button" data-a11y-set="images:on" title="Показывать">Вкл</button>
        <button type="button" data-a11y-set="images:off" title="Скрыть">Выкл</button>
    </div>
    <a href="#" class="a11y-panel__off">Обычная версия</a>
</div>
<?php endif; ?>
<main class="repo-main">
    <?php foreach (Flash::pull() as $flash): ?>
        <div class="repo-alert repo-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"
             role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"
             aria-live="<?= $flash['type'] === 'success' ? 'polite' : 'assertive' ?>">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
        </div>
    <?php endforeach; ?>
