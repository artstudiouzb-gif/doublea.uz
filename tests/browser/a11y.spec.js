const { test, expect } = require('@playwright/test');
const { AxeBuilder } = require('@axe-core/playwright');

/**
 * Автоматический аудит доступности. Раньше он был обязательной ручной
 * проверкой перед релизом — то есть на практике не выполнялся.
 *
 * Проверяются обе темы: контраст ломается именно при переключении, а глазами
 * тёмную тему смотрят реже. Правила — WCAG 2.0/2.1/2.2 уровней A и AA.
 *
 * axe не заменяет ручную проверку: он не оценивает осмысленность alt, порядок
 * табуляции и понятность формулировок. Он ловит регресс.
 */
const PAGES = [
    ['главная', '/'],
    ['лента новостей', '/news'],
    ['поиск', '/search?q=%D1%81%D1%82%D1%80%D0%B0%D1%82%D0%B5%D0%B3%D0%B8%D1%8F'],
    ['узбекская версия', '/uz'],
    // Формы и таблицы — там нарушений больше всего: у полей теряются подписи,
    // у ячеек — заголовки. Детальная новость и проект добавлены как самые
    // читаемые страницы сайта.
    ['детальная новость', '/news/ekspertnyy-kadrovyy-rezerv'],
    ['проект', '/projects/cifrovaya-transformaciya'],
    ['каталог документов', '/catalog/documenty'],
    ['контакты с формой', '/kontakty'],
];

for (const [name, url] of PAGES) {
    for (const theme of ['light', 'dark']) {
        test(`доступность: ${name} (${theme})`, async ({ page }) => {
            const response = await page.goto(url, { waitUntil: 'domcontentloaded' });

            // Минимальная фикстура CI содержит только главную; новости, проекты
            // и каталог появляются с демо-комплектом. Пропуск делаем видимым:
            // иначе аудит молча проверял бы страницу ошибки вместо страницы.
            test.skip(
                response !== null && response.status() === 404,
                `${url} — нет в этой сборке контента`
            );
            if (theme === 'dark') {
                await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));
                await page.waitForTimeout(200);
            }
            const result = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
                .analyze();

            const summary = result.violations.map((v) => {
                const where = v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join('; ');
                return `${v.id} (${v.impact}, ${v.nodes.length}): ${v.help} -> ${where}`;
            });
            expect(summary, summary.join('\n')).toEqual([]);
        });
    }
}
