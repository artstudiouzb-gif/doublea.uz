# Frontend V2 architecture

## Goal

Keep the existing CMS/admin, data models, media, multilingual content, menus, forms and publishing workflow, while replacing the public frontend with a new implementation that does not inherit the legacy theme.

## Isolation rules

- `app/Views/admin/**` stays the administration UI and is not redesigned as part of frontend v2.
- Existing `app/Views/site/**` remains the live legacy frontend until cutover.
- New views live under `app/Views/site/v2/**`.
- New CSS and JavaScript use the `aa2-*` namespace and live in `public/assets/css/frontend-v2.css` and `public/assets/js/frontend-v2.js`.
- Do not import the legacy public CSS bundle into v2.
- CMS data is reused; presentation markup is rebuilt.
- Legacy files are removed only after route-by-route parity and regression checks.

## Preview

`/frontend-v2-preview.php` is a temporary preview entrypoint. It requires an authenticated admin session and is marked `noindex`.

The preview currently reads from the existing CMS:

- localized site name;
- configured logo;
- active menu tree;
- active languages;
- current home page title, lead and SEO fields.

It intentionally does not render legacy page blocks yet.

## Rollout plan

1. Foundation: isolated shell, navigation, language UI, responsive tokens and preview entrypoint.
2. Home: create v2 block presenters for the selected home-page blocks.
3. Content pages: page head, text, media, breadcrumbs and side content.
4. News: listing, detail, gallery/video/audio states and related content.
5. Projects/team/catalog/forms: rebuild public templates while reusing existing models/controllers.
6. Accessibility/SEO/performance parity.
7. Cutover: switch public controllers to v2 views.
8. Cleanup: delete unused legacy frontend assets/templates only after production verification.

## Design-system direction

The foundation deliberately centralizes visual tokens in `frontend-v2.css`. The current warm neutral/orange palette is a provisional visual direction for the workspace, not a hard-coded dependency of the CMS. Brand decisions can therefore be changed without touching controllers or admin data.
