<?php

declare(strict_types=1);

use App\Core\ExternalJsonService;
use App\Core\CurrencyInformer;

test('Внешний JSON и валютный информер безопасно обрабатывают недоступный источник', function (): void {
// 1. Проверка обработки некорректного URL
$res = ExternalJsonService::fetch("invalid-url");
assert_same(null, $res);

// 2. Проверка CurrencyInformer (рендеринг либо HTML, либо пустая строка при оффлайн)
$html = CurrencyInformer::renderWidgetHtml();
assert_true(is_string($html));
});
