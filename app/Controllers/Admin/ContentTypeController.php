<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Slug;
use App\Core\View;
use App\Models\ContentType;

/**
 * Конструктор типов контента и их полей (задачи 131/132) — супер-администратор.
 */
final class ContentTypeController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/content_types/index', ['items' => ContentType::all()]);
    }

    public function store(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = Slug::make((string) ($_POST['slug'] ?? '') ?: $name);
        $hasTr = !empty($_POST['has_translations']);
        $description = trim((string) ($_POST['description'] ?? ''));
        $isPublic = !empty($_POST['is_public']);

        if ($name === '' || $slug === '') {
            Flash::error('Укажите название типа.');
        } elseif (ContentType::slugExists($slug)) {
            Flash::error('Тип с таким адресом уже существует.');
        } else {
            $id = ContentType::create($slug, $name, $hasTr, $description, $isPublic);
            Flash::success('Тип контента создан. Добавьте поля.');
            header('Location: /admin/content-types/' . $id . '/fields');
            exit;
        }
        header('Location: /admin/content-types');
        exit;
    }

    public function fields(array $params): void
    {
        Auth::requireSuperAdmin();
        $type = ContentType::findById((int) $params['id']);
        if (!$type) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('admin/content_types/fields', [
            'type' => $type,
            'fields' => ContentType::fields((int) $type['id']),
            'allTypes' => ContentType::all(),
        ]);
    }

    public function saveFields(array $params): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $type = ContentType::findById((int) $params['id']);
        if (!$type) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        ContentType::update(
            (int) $type['id'],
            trim((string) ($_POST['name'] ?? $type['name'])),
            !empty($_POST['has_translations']),
            trim((string) ($_POST['description'] ?? ($type['description'] ?? ''))),
            !empty($_POST['is_public'])
        );

        $fields = [];
        foreach ((array) ($_POST['fields'] ?? []) as $f) {
            $fname = preg_replace('/[^a-z0-9_]/i', '', trim((string) ($f['name'] ?? ''))) ?? '';
            $label = trim((string) ($f['label'] ?? ''));
            if ($fname === '' || $label === '') {
                continue;
            }
            $options = [];
            if (($f['field_type'] ?? '') === 'relation' && !empty($f['relation_type'])) {
                $options['relation_type'] = preg_replace('/[^a-z0-9_-]/i', '', (string) $f['relation_type']) ?? '';
            }
            $fields[] = [
                'name' => $fname,
                'label' => $label,
                'field_type' => (string) ($f['field_type'] ?? 'text'),
                'required' => !empty($f['required']),
                'options' => $options,
            ];
        }

        ContentType::replaceFields((int) $type['id'], $fields);
        Flash::success('Поля типа сохранены.');
        header('Location: /admin/content-types/' . (int) $type['id'] . '/fields');
        exit;
    }

    public function destroy(array $params): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        ContentType::delete((int) $params['id']);
        Flash::success('Тип контента и все его записи удалены.');
        header('Location: /admin/content-types');
        exit;
    }
}
