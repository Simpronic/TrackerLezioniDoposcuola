<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Student;

class StudentStatistics
{
    /**
     * Calcola le statistiche sull'intero storico dello studente.
     *
     * "Speso" comprende solo lezioni svolte e saldate; il debito comprende
     * lezioni svolte, fatturabili e prive di una data di pagamento.
     *
     * @return array{cancelled: int, completed: int, paid_total: float, debt_count: int, debt_total: float}
     */
    public function for(Student $student): array
    {
        $lessons = $student->relationLoaded('lessons')
            ? $student->lessons
            : $student->lessons()->get();

        $completed = $lessons->where('stato', 'svolta');
        $paid = $completed->filter(fn (Lesson $lesson): bool => $lesson->data_pagamento !== null);
        $debts = $completed->filter(fn (Lesson $lesson): bool => $lesson->da_fatturare && $lesson->data_pagamento === null);

        return [
            'cancelled' => $lessons->where('stato', 'annullata')->count(),
            'completed' => $completed->count(),
            'paid_total' => round($paid->sum('importo'), 2),
            'debt_count' => $debts->count(),
            'debt_total' => round($debts->sum('importo'), 2),
        ];
    }
}
