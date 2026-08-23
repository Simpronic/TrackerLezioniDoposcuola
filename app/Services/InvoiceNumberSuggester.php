<?php

namespace App\Services;

use App\Models\Lesson;

class InvoiceNumberSuggester
{
    /**
     * Restituisce il progressivo successivo nel formato "numero/anno".
     *
     * Lo stesso numero può comparire su più lezioni incluse nella medesima
     * fattura: distinct evita che queste righe incrementino il progressivo.
     */
    public function next(int $year): string
    {
        $highest = Lesson::query()
            ->whereNotNull('numero_fattura')
            ->where(function ($query) use ($year): void {
                $query->whereYear('data_fattura', $year)
                    ->orWhere('numero_fattura', 'like', '%/'.$year);
            })
            ->distinct()
            ->pluck('numero_fattura')
            ->reduce(function (int $maximum, string $number): int {
                // Sono accettati sia "12/2026" sia vecchi numeri solo numerici.
                return preg_match('/^(\d+)(?:\/\d{4})?$/', trim($number), $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0);

        return ($highest + 1).'/'.$year;
    }
}
