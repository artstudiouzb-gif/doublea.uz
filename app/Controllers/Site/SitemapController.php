<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\AppUrl;
use App\Core\Database;
use App\Core\Locale;
use App\Core\TranslationGroupHelper;
use App\Models\Language;
use App\Models\Page;
use App\Models\News;
use App\Models\Project;

final class SitemapController
{
    public function xml(): void
    {
        $this->sitemap();
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        $baseUrl = AppUrl::base();
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /api/\n";
        echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $baseUrl = AppUrl::base();
        $pages = Database::pdo()->query("SELECT p.* FROM pages p WHERE p.status = 'published' AND p.deleted_at IS NULL AND p.entity_type = 'page' ORDER BY p.updated_at DESC")->fetchAll();
        $news = Database::pdo()->query("SELECT n.* FROM news n WHERE n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL ORDER BY n.published_at DESC LIMIT 1000")->fetchAll();
        $projects = Database::pdo()->query("SELECT pr.* FROM pages pr WHERE pr.entity_type = 'project' AND pr.status = 'published' AND pr.deleted_at IS NULL ORDER BY pr.updated_at DESC")->fetchAll();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        // Pages
        foreach ($pages as $p) {
            $loc = self::canonicalUrl($baseUrl, 'pages', $p);
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . self::xmlEscape($loc) . '</loc>' . "\n";
            if (!empty($p['updated_at'])) {
                $xml .= '    <lastmod>' . date('c', strtotime((string) $p['updated_at'])) . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>' . (!empty($p['is_home']) ? '1.0' : '0.8') . '</priority>' . "\n";

            $xml .= self::alternateLinks($baseUrl, 'pages', (int) $p['id']);

            $xml .= '  </url>' . "\n";
        }

        // News
        foreach ($news as $n) {
            $loc = self::canonicalUrl($baseUrl, 'news', $n);
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . self::xmlEscape($loc) . '</loc>' . "\n";
            $pubDate = !empty($n['published_at']) ? (string) $n['published_at'] : (string) $n['created_at'];
            $xml .= '    <lastmod>' . date('c', strtotime($pubDate)) . '</lastmod>' . "\n";
            $xml .= '    <changefreq>daily</changefreq>' . "\n";
            $xml .= '    <priority>0.7</priority>' . "\n";

            $xml .= self::alternateLinks($baseUrl, 'news', (int) $n['id']);

            $xml .= '  </url>' . "\n";
        }

        // Projects
        foreach ($projects as $pr) {
            $loc = self::canonicalUrl($baseUrl, 'projects', $pr);
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . self::xmlEscape($loc) . '</loc>' . "\n";
            if (!empty($pr['updated_at'])) {
                $xml .= '    <lastmod>' . date('c', strtotime((string) $pr['updated_at'])) . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>monthly</changefreq>' . "\n";
            $xml .= '    <priority>0.6</priority>' . "\n";
            $xml .= self::alternateLinks($baseUrl, 'projects', (int) $pr['id']);
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';
        echo $xml;
    }

    /** @param array<string, mixed> $row */
    private static function canonicalUrl(string $baseUrl, string $type, array $row): string
    {
        $lang = (string) ($row['lang'] ?? Language::defaultCode());
        $slug = ltrim((string) ($row['slug'] ?? ''), '/');
        $path = match ($type) {
            'news' => 'news/' . $slug,
            'projects' => 'projects/' . $slug,
            default => !empty($row['is_home']) ? '' : $slug,
        };

        return $baseUrl . Locale::url($path, $lang);
    }

    private static function alternateLinks(string $baseUrl, string $type, int $recordId): string
    {
        $links = [];
        foreach (TranslationGroupHelper::getTranslations($type, $recordId) as $langCode => $row) {
            if (!self::isPublished($type, $row)) {
                continue;
            }
            $links[(string) $langCode] = self::canonicalUrl($baseUrl, $type, $row);
        }

        if (count($links) < 2) {
            return '';
        }

        $xml = '';
        foreach ($links as $langCode => $url) {
            $xml .= '    <xhtml:link rel="alternate" hreflang="' . self::xmlEscape($langCode)
                . '" href="' . self::xmlEscape($url) . '"/>' . "\n";
        }
        $defaultLang = Language::defaultCode();
        if (isset($links[$defaultLang])) {
            $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'
                . self::xmlEscape($links[$defaultLang]) . '"/>' . "\n";
        }

        return $xml;
    }

    /** @param array<string, mixed> $row */
    private static function isPublished(string $type, array $row): bool
    {
        if (($row['status'] ?? '') !== 'published' || !empty($row['deleted_at'])) {
            return false;
        }

        if ($type !== 'news') {
            return true;
        }

        $publishedAt = trim((string) ($row['published_at'] ?? ''));
        $timestamp = $publishedAt !== '' ? strtotime($publishedAt) : false;

        return $timestamp !== false && $timestamp <= time();
    }

    private static function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function rss(array $params = []): void
    {
        header('Content-Type: application/rss+xml; charset=utf-8');

        $baseUrl = AppUrl::base();
        $siteTitle = \App\Models\Setting::get('site_name', 'ArtStudio');

        $lang = (string) ($params['lang'] ?? '');
        $sql = "SELECT n.* FROM news n WHERE n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL";
        $sqlParams = [];
        if ($lang !== '') {
            $sql .= " AND n.lang = :lang";
            $sqlParams[':lang'] = $lang;
        }
        $sql .= " ORDER BY n.published_at DESC LIMIT 50";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($sqlParams);
        $items = $stmt->fetchAll();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2004/05/atom">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . htmlspecialchars($siteTitle, ENT_QUOTES) . '</title>' . "\n";
        $xml .= '    <link>' . htmlspecialchars($baseUrl, ENT_QUOTES) . '</link>' . "\n";
        $xml .= '    <description>' . htmlspecialchars($siteTitle . ' RSS Feed', ENT_QUOTES) . '</description>' . "\n";
        $xml .= '    <language>' . htmlspecialchars($lang !== '' ? $lang : Language::defaultCode(), ENT_QUOTES) . '</language>' . "\n";

        foreach ($items as $n) {
            $link = self::canonicalUrl($baseUrl, 'news', $n);
            $pubDate = date('r', strtotime((string) $n['published_at']));
            $title = (string) $n['title'];
            $excerpt = (string) ($n['excerpt'] ?: $n['title']);
            $image = (string) ($n['image'] ?? '');

            $xml .= '    <item>' . "\n";
            $xml .= '      <title>' . htmlspecialchars($title, ENT_QUOTES) . '</title>' . "\n";
            $xml .= '      <link>' . htmlspecialchars($link, ENT_QUOTES) . '</link>' . "\n";
            $xml .= '      <guid isPermaLink="true">' . htmlspecialchars($link, ENT_QUOTES) . '</guid>' . "\n";
            $xml .= '      <pubDate>' . $pubDate . '</pubDate>' . "\n";
            $xml .= '      <description><![CDATA[' . $excerpt . ']]></description>' . "\n";
            if ($image !== '') {
                $imgUrl = str_starts_with($image, 'http') ? $image : $baseUrl . '/' . ltrim($image, '/');
                $xml .= '      <enclosure url="' . htmlspecialchars($imgUrl, ENT_QUOTES) . '" type="image/jpeg" />' . "\n";
            }
            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>';
        echo $xml;
    }
}
