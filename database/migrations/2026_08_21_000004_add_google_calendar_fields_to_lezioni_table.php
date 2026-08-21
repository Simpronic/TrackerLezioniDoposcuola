<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lezioni', function (Blueprint $table): void {
            // L'ID consente di aggiornare lo stesso evento senza crearne duplicati.
            $table->string('google_calendar_event_id')->nullable()->after('note')->index();
            $table->text('google_calendar_event_url')->nullable()->after('google_calendar_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('lezioni', function (Blueprint $table): void {
            $table->dropColumn(['google_calendar_event_id', 'google_calendar_event_url']);
        });
    }
};
