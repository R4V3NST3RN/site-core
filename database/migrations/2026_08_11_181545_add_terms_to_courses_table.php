<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Cache termínů lekcí z externího systému, uložená tak, jak
            // dorazila. Není to náš záznam, ale read-only kopie cizího
            // stavu — proto json na kurzu a ne samostatná tabulka lekcí
            // s relací, kterou bychom museli udržovat konzistentní.
            $table->json('terms')->nullable()->after('blocks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('terms');
        });
    }
};
