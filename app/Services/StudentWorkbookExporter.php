<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Student;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StudentWorkbookExporter
{
    // Formato numerico esplicito: non dipende dalla lingua di PHP o del server.
    private const EURO_FORMAT = '[$€-it-IT] #,##0.00';

    // L'ordine rispecchia l'anno scolastico e i nomi dei fogli del modello allegato.
    private const MONTHS = [
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
    ];

    public function make(Student $student, int $startYear): Spreadsheet
    {
        // Il template contiene impaginazione e formule di base: viene caricato e
        // compilato in memoria senza modificare il file originale in resources/.
        $spreadsheet = IOFactory::load(resource_path('templates/ModelloBase.xlsx'));
        $lessons = $student->lessons()
            ->whereBetween('data', ["{$startYear}-09-01", ($startYear + 1).'-08-31'])
            ->orderBy('data')->orderBy('ora_inizio')->get();

        foreach (self::MONTHS as $month => $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }

            $sheet->setCellValue('A1', "Foglio orario Lezioni svolte · {$student->nome_completo} · {$startYear}/".($startYear + 1));
            $sheet->getColumnDimension('G')->setVisible(false);
            $this->clearRows($sheet);

            // Settembre-dicembre usano startYear; gennaio-agosto l'anno successivo.
            $year = $month >= 9 ? $startYear : $startYear + 1;
            $monthlyLessons = $lessons->filter(fn (Lesson $lesson) => $lesson->data->year === $year && $lesson->data->month === $month)->values();

            foreach ($monthlyLessons as $index => $lesson) {
                $row = 3 + $index;
                if ($row > 3) {
                    $sheet->duplicateStyle($sheet->getStyle('A3:G3'), "A{$row}:G{$row}");
                }

                $sheet->setCellValue("A{$row}", Date::dateTimeToExcel($lesson->data));
                $sheet->setCellValue("B{$row}", $this->timeValue($lesson->ora_inizio));
                $sheet->setCellValue("C{$row}", $this->timeValue($lesson->ora_fine));
                $sheet->setCellValue("D{$row}", $lesson->stato === 'svolta' ? "=(C{$row}-B{$row})*24" : 0);
                $sheet->setCellValue("E{$row}", $lesson->argomento);
                $sheet->setCellValue("F{$row}", $this->paymentLabel($lesson));
                // La colonna G è nascosta e conserva la tariffa storica per riga.
                // In questo modo i totali restano corretti anche con tariffe diverse.
                $sheet->setCellValue("G{$row}", $lesson->stato === 'svolta' && $lesson->da_fatturare ? (float) $lesson->tariffa_oraria_applicata : 0);
            }

            $lastRow = max(19, 2 + $monthlyLessons->count());
            $sheet->getStyle("A3:A{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $sheet->getStyle("B3:C{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');
            $sheet->getStyle("D3:D{$lastRow}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("G3:G{$lastRow}")->getNumberFormat()->setFormatCode(self::EURO_FORMAT);
        }

        $this->fillSummary($spreadsheet, $student);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function clearRows($sheet): void
    {
        // Puliamo solo le righe realmente presenti nel modello; quando le lezioni
        // sono di più, make() crea e formatta automaticamente le righe successive.
        $lastRow = max(19, $sheet->getHighestDataRow());

        for ($row = 3; $row <= $lastRow; $row++) {
            foreach (range('A', 'G') as $column) {
                $sheet->setCellValue("{$column}{$row}", null);
            }
        }
    }

    private function fillSummary(Spreadsheet $spreadsheet, Student $student): void
    {
        $sheet = $spreadsheet->getSheetByName('Calcoli');
        if (! $sheet) {
            return;
        }

        // SUMPRODUCT moltiplica ore e tariffa nascosta di ogni lezione; la seconda
        // formula limita il conteggio alle righe fatturabili non ancora pagate.
        foreach (array_values(self::MONTHS) as $index => $monthName) {
            $row = 3 + $index;
            $sheet->setCellValue("B{$row}", "=SUMPRODUCT('{$monthName}'!D3:D1000,'{$monthName}'!G3:G1000)");
            $sheet->setCellValue("E{$row}", "=SUMPRODUCT(('{$monthName}'!F3:F1000=\"No\")*'{$monthName}'!D3:D1000*'{$monthName}'!G3:G1000)");
        }

        $sheet->setCellValue('G5', (float) $student->tariffa_oraria);
        $sheet->setCellValue('H5', '=SUM(B3:B14)');
        $sheet->setCellValue('K5', $student->pagante_codice_fiscale);
        $sheet->setCellValue('K6', $student->pagante_indirizzo);
        $sheet->setCellValue('K7', $student->pagante_nome);
        $sheet->setCellValue('K8', $student->pagante_cognome);
        $sheet->getStyle('B3:B14')->getNumberFormat()->setFormatCode(self::EURO_FORMAT);
        $sheet->getStyle('E3:E14')->getNumberFormat()->setFormatCode(self::EURO_FORMAT);
        $sheet->getStyle('G5:H5')->getNumberFormat()->setFormatCode(self::EURO_FORMAT);
    }

    private function timeValue(string $time): float
    {
        // Excel memorizza l'orario come frazione di una giornata (12:00 = 0,5).
        $parsed = Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time);

        return ($parsed->hour * 3600 + $parsed->minute * 60 + $parsed->second) / 86400;
    }

    private function paymentLabel(Lesson $lesson): string
    {
        if ($lesson->stato !== 'svolta') {
            return 'Non eseguita';
        }

        if (! $lesson->da_fatturare) {
            return 'Non dovuta';
        }

        return $lesson->data_pagamento ? 'Sì' : 'No';
    }
}
