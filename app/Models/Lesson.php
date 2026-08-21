<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    use HasUuids;

    protected $table = 'lezioni';

    protected $fillable = [
        'studente_id', 'data', 'ora_inizio', 'ora_fine', 'argomento', 'stato',
        'tariffa_oraria_applicata', 'fatturata', 'numero_fattura', 'data_fattura',
        'stato_fattura', 'data_pagamento', 'note',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date', 'data_fattura' => 'date', 'data_pagamento' => 'date',
            'fatturata' => 'boolean', 'tariffa_oraria_applicata' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'studente_id');
    }

    public function getDurataOreAttribute(): float
    {
        $start = strtotime($this->ora_inizio);
        $end = strtotime($this->ora_fine);
        return max(0, ($end - $start) / 3600);
    }

    public function getImportoAttribute(): float
    {
        return $this->stato === 'svolta' ? round($this->durata_ore * (float) $this->tariffa_oraria_applicata, 2) : 0;
    }
}
