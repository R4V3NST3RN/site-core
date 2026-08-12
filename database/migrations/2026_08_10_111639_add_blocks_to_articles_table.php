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
        Schema::table('articles', function (Blueprint $table) {
            // 'content' zůstává nedotčený jako legacy fallback pro články
            // napsané před blokovým obsahem — renderer sáhne po 'blocks'
            // jen když jsou vyplněné.
            $table->json('blocks')->nullable()->after('content');
            $table->string('template')->nullable()->after('blocks');
            $table->string('category')->nullable()->after('template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['blocks', 'template', 'category']);
        });
    }
};
