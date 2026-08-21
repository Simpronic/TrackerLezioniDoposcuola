<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment('local')) { return; }

        $student = Student::create([
            'nome' => 'Giulia', 'cognome' => 'Bianchi', 'anno_ingresso' => now()->year,
            'attivo' => true, 'tariffa_oraria' => 22,
            'pagante_nome' => 'Marco', 'pagante_cognome' => 'Bianchi',
            'pagante_codice_fiscale' => 'BNCMRC80A01H501U', 'pagante_indirizzo' => 'Via Roma 10',
        ]);

        foreach ([
            [-55, 'svolta', true], [-28, 'svolta', false], [-7, 'svolta', true], [3, 'programmata', false], [10, 'programmata', false],
        ] as [$days, $status, $paid]) {
            Lesson::create([
                'studente_id' => $student->id, 'data' => today()->addDays($days),
                'ora_inizio' => '15:00', 'ora_fine' => '16:30', 'argomento' => 'Matematica · equazioni e problemi',
                'stato' => $status, 'tariffa_oraria_applicata' => 22, 'fatturata' => $paid,
                'numero_fattura' => $paid ? 'F-'.abs($days) : null,
                'data_fattura' => $paid ? today()->addDays($days + 1) : null,
                'stato_fattura' => $paid ? 'pagata' : null,
                'data_pagamento' => $paid ? today()->addDays($days + 5) : null,
            ]);
        }
    }
}
