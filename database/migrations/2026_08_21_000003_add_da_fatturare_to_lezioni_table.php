<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lezioni', function (Blueprint $table): void {
            $table->boolean('da_fatturare')->default(true)->after('tariffa_oraria_applicata')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lezioni', fn (Blueprint $table) => $table->dropColumn('da_fatturare'));
    }
};
