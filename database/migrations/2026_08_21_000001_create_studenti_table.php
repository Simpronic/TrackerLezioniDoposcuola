<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('studenti', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nome', 100);
            $table->string('cognome', 100);
            $table->unsignedSmallInteger('anno_ingresso');
            $table->boolean('attivo')->default(true);
            $table->decimal('tariffa_oraria', 8, 2);
            $table->string('pagante_nome', 100)->nullable();
            $table->string('pagante_cognome', 100)->nullable();
            $table->string('pagante_codice_fiscale', 16)->nullable();
            $table->string('pagante_indirizzo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('studenti'); }
};
