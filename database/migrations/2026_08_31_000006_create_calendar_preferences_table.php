<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Consente di usare la stessa migration anche dopo un'importazione SQL.
        if (Schema::hasTable('calendar_preferences')) {
            return;
        }

        Schema::create('calendar_preferences', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('notifications_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_preferences');
    }
};
