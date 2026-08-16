<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Language;
use App\Models\Setting;

final class SeoHelper
{
    /**
     * Генерирует hreflang-теги для всех активных языков сайта.
     */
    public static function hreflangTags(string $appUrl): string
    {
        if (!Database::isConnected()) {
            return "";
        }

        $activeCodes = Language::activeCodes();
        if (count($activeCodes) <= 1) {
            return "";
        }

        $path = Locale::path();
        $defaultLang = Language::defaultCode();
        $html = "";

        foreach ($activeCodes as $langCode) {
            $localizedUrl = rtrim($appUrl, "/") . Locale::url($path, $langCode);
            $html .= "<link rel=\"alternate\" hreflang=\"" . htmlspecialchars($langCode, ENT_QUOTES) . "\" href=\"" . htmlspecialchars($localizedUrl, ENT_QUOTES) . "\" />\n";
        }

        // x-default ссылается на язык по умолчанию
        $defaultUrl = rtrim($appUrl, "/") . Locale::url($path, $defaultLang);
        $html .= "<link rel=\"alternate\" hreflang=\"x-default\" href=\"" . htmlspecialchars($defaultUrl, ENT_QUOTES) . "\" />\n";

        return $html;
    }

    /**
     * Генерирует JSON-LD микроразметку организации (GovernmentOrganization / Organization).
     */
    public static function organizationSchema(string $appUrl): string
    {
        $siteName = Setting::getLocalized("site_name", Locale::current(), "Государственный портал Республики Узбекистан");
        $logo = Setting::get("logo_url", "");
        if ($logo !== "" && str_starts_with($logo, "/")) {
            $logo = rtrim($appUrl, "/") . $logo;
        }

        $phone = Setting::get("contact_phone", "") ?: Setting::get("site_phone", "");
        $email = Setting::get("contact_email", "") ?: Setting::get("site_email", "");
        $address = Setting::get("contact_address", "");

        $data = [
            "@context" => "https://schema.org",
            "@type" => "GovernmentOrganization",
            "name" => $siteName,
            "url" => $appUrl,
        ];

        if ($logo !== "") {
            $data["logo"] = $logo;
        }

        if ($phone !== "" || $email !== "") {
            $contactPoint = ["@type" => "ContactPoint"];
            if ($phone !== "") { $contactPoint["telephone"] = $phone; }
            if ($email !== "") { $contactPoint["email"] = $email; }
            $contactPoint["contactType"] = "customer service";
            $data["contactPoint"] = $contactPoint;
        }

        if ($address !== "") {
            $data["address"] = [
                "@type" => "PostalAddress",
                "streetAddress" => $address,
            ];
        }

        return "<script type=\"application/ld+json\">" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }

    /**
     * Генерирует JSON-LD микроразметку хлебных крошек (BreadcrumbList).
     *
     * @param array<int, array{name: string, url?: string}> $items
     */
    public static function breadcrumbSchema(string $appUrl, array $items): string
    {
        if ($items === []) {
            return "";
        }

        $list = [];
        $pos = 1;

        foreach ($items as $item) {
            $name = trim((string) ($item["name"] ?? ""));
            if ($name === "") { continue; }

            $url = (string) ($item["url"] ?? "");
            if ($url !== "" && str_starts_with($url, "/")) {
                $url = rtrim($appUrl, "/") . $url;
            }

            $listItem = [
                "@type" => "ListItem",
                "position" => $pos++,
                "name" => $name,
            ];

            if ($url !== "") {
                $listItem["item"] = $url;
            }

            $list[] = $listItem;
        }

        if ($list === []) {
            return "";
        }

        $data = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $list,
        ];

        return "<script type=\"application/ld+json\">" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }

    /** Внешних шрифтовых ресурсов больше нет: весь каталог самохостится. */
    public static function resourceHintsHtml(): string
    {
        return '';
    }

    /**
     * Подключает иконки устройств и веб-манифест. Ссылки на файлы иконок
     * выводятся только если файлы действительно лежат в public/: иначе
     * браузер на каждой странице получал три ответа 404. Загруженная в
     * настройках фавиконка подключается отдельно (в шапке сайта), манифест
     * собирается из настроек контроллером.
     */
    public static function faviconsHtml(string $appUrl): string
    {
        $html = '';
        foreach (Favicon::staticIcons() as $tag) {
            $html .= $tag . "\n";
        }

        return $html . "<link rel=\"manifest\" href=\"/manifest.webmanifest\">\n";
    }
}

