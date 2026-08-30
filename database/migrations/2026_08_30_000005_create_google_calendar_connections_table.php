<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_connections', function (Blueprint $table): void {
            // L'app usa un solo account Calendar: una singola riga è sufficiente.
            $table->unsignedTinyInteger('id')->primary();
            $table->text('refresh_token');
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_connections');
    }
};
