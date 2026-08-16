<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Auth;
use App\Core\HeaderConfig;
use App\Core\Locale;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;

// Temporary, admin-only entrypoint for the isolated frontend v2 workspace.
// The live public routes remain on the legacy frontend until v2 reaches parity.
Auth::requireLogin();

$lang = Locale::current();
$siteName = Setting::getLocalized('site_name', $lang, 'Double A');
$page = Page::findHome($lang) ?: [
    'title' => $siteName,
    'lead' => '',
    'meta_title' => $siteName,
    'meta_description' => '',
];

$headerConfig = HeaderConfig::get();
$logo = trim((string) ($headerConfig['logo_by_lang'][$lang] ?? ''));
if ($logo === '') {
    $logo = trim((string) Setting::get('logo_url', ''));
}

$menuItems = MenuItem::activeForLang($lang);
$languages = Language::active();

require APP_ROOT . '/app/Views/site/v2/home.php';
