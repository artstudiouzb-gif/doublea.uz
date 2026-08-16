# doublea.uz

Web project for **doublea.uz**.

The repository keeps the existing PHP/MySQL administration and content-management backend while the public frontend is being rebuilt as an independent V2 layer.

## Structure

- `app/Controllers/Admin/`, `app/Views/admin/` — administration and CMS workflows.
- `app/Models/`, `app/Core/` — application and content infrastructure.
- `app/Views/site/v2/` — new public frontend templates.
- `public/assets/css/frontend-v2.css` — isolated V2 styles.
- `public/assets/js/frontend-v2.js` — isolated V2 interactions.
- `templates/` — content blocks and widgets used by the backend.

The legacy public frontend remains only during the migration period and is not the design source for V2.

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Apache or nginx with the document root pointed to `public/`

## Development checks

```bash
php tests/run.php
composer analyse
npm ci
npm run check:assets
npm run test:browser
```

Production runtime does not require Node.js tooling; npm and Composer development dependencies are used for validation and CI.
