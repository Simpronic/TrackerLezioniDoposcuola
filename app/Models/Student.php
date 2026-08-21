<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasUuids;

    protected $table = 'studenti';

    protected $fillable = [
        'nome', 'cognome', 'anno_ingresso', 'attivo', 'tariffa_oraria',
        'pagante_nome', 'pagante_cognome', 'pagante_codice_fiscale', 'pagante_indirizzo',
    ];

    protected function casts(): array
    {
        return ['attivo' => 'boolean', 'tariffa_oraria' => 'decimal:2'];
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'studente_id');
    }

    public function getNomeCompletoAttribute(): string
    {
        return trim($this->nome.' '.$this->cognome);
    }
}
