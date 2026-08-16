<?php

declare(strict_types=1);

namespace App\Core;

final class CurrencyInformer
{
    private const CBU_API = "https://cbu.uz/ru/arkhiv-kursov-valyut/json/";

    /**
     * Получает актуальные курсы основных валют (USD, EUR, RUB).
     *
     * @return array<string, array{code:string, name:string, rate:string, diff:string}>
     */
    public static function rates(): array
    {
        $data = ExternalJsonService::fetch(self::CBU_API, 3600);
        if (!is_array($data)) {
            return [];
        }

        $rates = [];
        $targetCodes = ["USD", "EUR", "RUB"];

        foreach ($data as $row) {
            if (is_array($row) && isset($row["Ccy"]) && in_array($row["Ccy"], $targetCodes, true)) {
                $rates[$row["Ccy"]] = [
                    "code" => (string) $row["Ccy"],
                    "name" => (string) ($row["CcyNm_RU"] ?? $row["Ccy"]),
                    "rate" => (string) ($row["Rate"] ?? "0"),
                    "diff" => (string) ($row["Diff"] ?? "0"),
                ];
            }
        }

        return $rates;
    }

    /**
     * Форматирует компактную HTML-плашку для шапки или сайдбара.
     */
    public static function renderWidgetHtml(): string
    {
        $rates = self::rates();
        if ($rates === []) {
            return "";
        }

        $html = "<div class=\"currency-widget\" title=\"Курсы валют Центрального Банка\">";

        foreach (["USD", "EUR", "RUB"] as $code) {
            if (!isset($rates[$code])) { continue; }
            $r = $rates[$code];
            $diff = (float) $r["diff"];
            $diffClass = $diff > 0 ? "is-up" : ($diff < 0 ? "is-down" : "");
            $diffSign = $diff > 0 ? "+" : ($diff < 0 ? "-" : "");

            $html .= "<span class=\"currency-item\">";
            $html .= "<span class=\"currency-item__code\">" . $code . "</span> ";
            $html .= "<span class=\"currency-item__rate\">" . number_format((float) $r["rate"], 0, "", " ") . "</span>";
            if ($diff !== 0.0) {
                $html .= " <span class=\"currency-item__diff " . $diffClass . "\">" . $diffSign . abs($diff) . "</span>";
            }
            $html .= "</span>";
        }
        $html .= "</div>";

        return $html;
    }
}

