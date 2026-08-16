# Developer Guide (DEV.md) — ArtStudio CMS (asdr)

Welcome to the **ArtStudio CMS** developer documentation. This guide details how to set up your local development environment, run test suites, inspect static analysis, and extend CMS functionality (such as adding new block types).

---

## 1. System Requirements & Local Setup

### Requirements
- **PHP**: 8.2 or higher
- **Extensions**: `pdo_mysql`, `mbstring`, `gd`, `openssl`, `json`, `session`, `ctype`
- **Database**: MariaDB 10.5+ or MySQL 8.0+

### Installation & Launch
1. Clone the repository into your local server environment:
   ```bash
   git clone https://github.com/artstudiouzb-gif/asdr.git
   cd asdr
   ```
2. Point your local web server (Nginx, Apache, or OpenServer) document root to the `public/` folder.
3. Open `http://localhost` or your local domain. The interactive 4-step web installer will launch automatically if `config.php` does not exist.

---

## 2. Developer Commands & Testing

Shortcut commands are configured via `composer.json` and `Makefile`:

| Command | Action | Description |
|---|---|---|
| `composer test` | `php tests/run.php` | Runs full unit & integration test suite (570+ tests) |
| `composer lint` | `php -l ...` | Syntax check across all `app/` PHP files |
| `composer analyse` | `phpstan analyse` | Runs PHPStan static analysis |
| `composer check` | `lint + test` | Combined syntax lint and unit testing |
| `composer smoke` | `php scripts/smoke.php` | Runs smoke checks against a live instance |
| `make help` | `make ...` | Shows Makefile command options |

### Running Tests with MySQL / MariaDB
By default, unit tests use an isolated test environment. To run DB-dependent integration tests:
```bash
TEST_DB_HOST=127.0.0.1 TEST_DB_PORT=3306 TEST_DB_DATABASE=test_asdr_ci TEST_DB_USERNAME=root TEST_DB_PASSWORD= php tests/run.php
```

---

## 3. Architecture & Code Structure

```text
asdr/
├── app/
│   ├── Controllers/   # Admin and Site controllers (PageController, NewsController, etc.)
│   ├── Core/          # Base components (Router, Cache, View, Locale, AdminUi, SecretBox)
│   ├── Models/        # Data models (Page, News, Project, Block, BlockSnippet, Setting)
│   └── Views/         # PHP templates (admin/, site/, blocks/, install/)
├── docs/              # Developer and architectural documentation
├── public/            # Public document root (index.php, uploads/, assets/)
├── tests/             # Comprehensive unit and integration tests
├── composer.json      # Composer manifest (dev dependencies & scripts)
└── Makefile           # Convenient dev task shortcuts
```

---

## 4. How to Add a New Page Block

Page blocks are modular components rendered via `BlockRenderer`. To add a new block type (e.g. `event_grid`):

### Step 1: Register in `BlockTypeRegistry`
Open [app/Core/BlockTypeRegistry.php](file:///C:/Users/Ulugbek/Documents/Codex/2026-07-26/new-chat/work/asdr/app/Core/BlockTypeRegistry.php) and add the new type definition:
```php
public const TYPES = [
    // ...
    'event_grid' => 'Сетка мероприятий',
];
```
Define default data fields in `defaultsFor(string $type)`:
```php
'event_grid' => [
    'title' => 'Предстоящие события',
    'items' => [],
],
```

### Step 2: Create Template View
Create `app/Views/blocks/event_grid.php`:
```php
<?php
/** @var array $block */
/** @var array $data */
$title = (string) ($data['title'] ?? '');
?>
<section class="block-event-grid" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?php if ($title !== ''): ?>
            <h2><?= htmlspecialchars($title, ENT_QUOTES) ?></h2>
        <?php endif; ?>
        <!-- Render block content -->
    </div>
</section>
```

### Step 3: Add Unit Test
Add a test case in `tests/` verifying that `BlockRenderer::render()` produces clean, sanitized HTML output for `event_grid`.

---

## 5. Production Build & Deployment

- Production runtime dependencies are strictly zero (standard PHP extensions only).
- Dev tools (`phpstan`, `phpunit`, linters) are scoped under `require-dev`.
- Deployments require uploading repository files, setting directory permissions on `storage/` and `public/uploads/`, and running database migrations if applicable.
