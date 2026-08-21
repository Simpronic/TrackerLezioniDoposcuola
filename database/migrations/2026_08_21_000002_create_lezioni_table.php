<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lezioni', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('studente_id')->constrained('studenti')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('data')->index();
            $table->time('ora_inizio');
            $table->time('ora_fine');
            $table->text('argomento')->nullable();
            $table->string('stato', 20)->default('svolta');
            $table->decimal('tariffa_oraria_applicata', 8, 2);
            $table->boolean('fatturata')->default(false);
            $table->string('numero_fattura', 50)->nullable()->index();
            $table->date('data_fattura')->nullable();
            $table->string('stato_fattura', 20)->nullable();
            $table->date('data_pagamento')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['studente_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lezioni');
    }
};
