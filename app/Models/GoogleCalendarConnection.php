<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarConnection extends Model
{
    /** La connessione è un record singleton creato esplicitamente con ID 1. */
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['id', 'refresh_token', 'connected_at'];

    protected function casts(): array
    {
        return [
            // Il token viene cifrato con APP_KEY prima di essere scritto nel DB.
            'refresh_token' => 'encrypted',
            'connected_at' => 'datetime',
        ];
    }
}
