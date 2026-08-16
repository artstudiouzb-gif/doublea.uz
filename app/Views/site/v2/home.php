<?php

/** @var array<string, mixed> $page */
/** @var string $siteName */

$metaTitle = trim((string) ($page['meta_title'] ?? '')) ?: trim((string) ($page['title'] ?? '')) ?: $siteName;
$metaDescription = trim((string) ($page['meta_description'] ?? ''));
$title = trim((string) ($page['title'] ?? '')) ?: $siteName;
$lead = trim((string) ($page['lead'] ?? ''));

require __DIR__ . '/_header.php';
?>
<main id="main-content" class="aa2-main">
    <section class="aa2-hero">
        <div class="aa2-shell aa2-hero__grid">
            <div class="aa2-hero__copy">
                <div class="aa2-preview-label" aria-label="Frontend version 2 preview">
                    <span class="aa2-preview-label__dot" aria-hidden="true"></span>
                    Frontend V2
                </div>
                <h1 class="aa2-hero__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h1>
                <?php if ($lead !== ''): ?>
                    <p class="aa2-hero__lead"><?= nl2br(htmlspecialchars($lead, ENT_QUOTES)) ?></p>
                <?php endif; ?>
            </div>

            <div class="aa2-hero__visual" aria-hidden="true">
                <div class="aa2-hero__monogram">AA</div>
                <div class="aa2-hero__orbit aa2-hero__orbit--one"></div>
                <div class="aa2-hero__orbit aa2-hero__orbit--two"></div>
            </div>
        </div>
    </section>

    <section class="aa2-foundation" aria-label="Frontend v2 foundation status">
        <div class="aa2-shell aa2-foundation__grid">
            <article class="aa2-foundation__item">
                <span class="aa2-foundation__index">01</span>
                <h2>CMS</h2>
                <p>Бренд, меню, языки и главная страница уже читаются из существующей CMS.</p>
            </article>
            <article class="aa2-foundation__item">
                <span class="aa2-foundation__index">02</span>
                <h2>Isolated</h2>
                <p>Новый CSS и JavaScript не используют legacy-классы и не меняют текущий публичный сайт.</p>
            </article>
            <article class="aa2-foundation__item">
                <span class="aa2-foundation__index">03</span>
                <h2>Ready</h2>
                <p>Следующий этап — собрать новую главную и затем новые шаблоны новостей, страниц и проектов.</p>
            </article>
        </div>
    </section>
</main>
<?php require __DIR__ . '/_footer.php'; ?>
