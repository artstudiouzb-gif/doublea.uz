<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\BlockSnippet;
use App\Models\Language;
use App\Models\Page;

/**
 * Шаблоны целых страниц: снимок всех блоков языкового стека (включая дочерние
 * блоки колонок и активность) и применение к любой странице — добавлением к
 * текущим блокам или полной заменой. При вставке блоки получают новые id
 * (custom_css скоупится по #block-{id} — конфликтов не возникает).
 */
final class SnippetController
{
    private function resolveLang(): string
    {
        $lang = (string) ($_POST['block_lang'] ?? Language::defaultCode());
        return Language::isActive($lang) ? $lang : Language::defaultCode();
    }

    public function save(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $pageId = (int) $params['id'];
        $page = Page::findById($pageId);
        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = $this->resolveLang();
        $name = trim((string) ($_POST['snippet_name'] ?? ''));
        if ($name === '') {
            Flash::error('Укажите название шаблона.');
            $this->back($pageId, $lang);
        }

        // Снимок целой страницы: верхний уровень + дочерние блоки колонок.
        $blocks = BlockSnippet::captureFromPage($pageId, $lang);

        if ($blocks === []) {
            Flash::error('На этом языке нет блоков для сохранения.');
            $this->back($pageId, $lang);
        }

        BlockSnippet::create($name, $blocks);
        Flash::success('Шаблон «' . $name . '» сохранён (' . count($blocks) . ' блоков).');
        $this->back($pageId, $lang);
    }

    public function insert(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $pageId = (int) $params['id'];
        $page = Page::findById($pageId);
        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = $this->resolveLang();
        $snippet = BlockSnippet::findById((int) ($_POST['snippet_id'] ?? 0));
        if ($snippet === null) {
            Flash::error('Шаблон не найден.');
            $this->back($pageId, $lang);
        }

        $blocks = json_decode((string) $snippet['blocks_json'], true);
        if (!is_array($blocks)) {
            Flash::error('Шаблон повреждён.');
            $this->back($pageId, $lang);
        }

        $replace = ($_POST['mode'] ?? 'append') === 'replace';
        // Замена удаляет блоки безвозвратно — сначала снимаем автокопию.
        $backup = $replace ? BlockSnippet::autoBackup($pageId, $lang, (string) ($page['title'] ?? '')) : null;
        $count = BlockSnippet::applyToPage($blocks, $pageId, $lang, $replace);

        Cache::forgetPrefix('page:');
        Flash::success(($replace ? 'Страница заменена шаблоном. ' : '') . 'Вставлено блоков: ' . $count . '.');
        if ($backup !== null) {
            Flash::success('Прежние блоки сохранены как шаблон «' . $backup . '» — применить его с режимом «Заменить», чтобы вернуть как было.');
        }
        $this->back($pageId, $lang);
    }

    /**
     * Готовая сборка страницы (App\Core\PagePresets): те же блоки, что и у
     * пользовательского шаблона, только описаны в коде и приходят с уже
     * расставленными фонами и отступами.
     */
    public function applyPreset(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $pageId = (int) $params['id'];
        $page = Page::findById($pageId);
        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = $this->resolveLang();
        $preset = \App\Core\PagePresets::find((string) ($_POST['preset'] ?? ''), $lang);
        if ($preset === null) {
            Flash::error('Сборка не найдена.');
            $this->back($pageId, $lang);
        }

        $replace = ($_POST['mode'] ?? 'append') === 'replace';
        // Замена удаляет блоки безвозвратно — сначала снимаем автокопию.
        $backup = $replace ? BlockSnippet::autoBackup($pageId, $lang, (string) ($page['title'] ?? '')) : null;
        $count = BlockSnippet::applyToPage($preset['blocks'], $pageId, $lang, $replace);

        $presetKey = (string) ($_POST['preset'] ?? '');
        if ($presetKey === 'home' || !empty($page['is_home']) || (string) ($page['slug'] ?? '') === 'home') {
            \App\Core\Database::pdo()->exec("UPDATE pages SET is_home = 0 WHERE id <> " . (int) $pageId);
            \App\Core\Database::pdo()->exec("UPDATE pages SET is_home = 1, status = 'published' WHERE id = " . (int) $pageId);
        }

        Cache::flush();
        Flash::success(sprintf(
            'Сборка «%s» применена: блоков — %d. Замените тексты-заготовки своим содержимым.',
            $preset['name'],
            $count
        ));
        if ($backup !== null) {
            Flash::success('Прежние блоки сохранены как шаблон «' . $backup . '» — применить его с режимом «Заменить», чтобы вернуть как было.');
        }
        $this->back($pageId, $lang);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        BlockSnippet::delete((int) $params['id']);
        Flash::success('Шаблон удалён.');
        $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/pages';
        header('Location: ' . (str_starts_with($referer, '/') ? $referer : '/admin/pages'));
        exit;
    }

    private function back(int $pageId, string $lang): never
    {
        header('Location: /admin/pages/' . $pageId . '/edit?block_lang=' . urlencode($lang));
        exit;
    }
}
