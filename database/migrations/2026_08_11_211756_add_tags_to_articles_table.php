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
            // Ploché pole stringů, ne relace — tagy jsou volný štítek,
            // ne entita s vlastním životem. Konzistentní s blocks/terms
            // a snadno generovatelné (i AI agentem).
            $table->json('tags')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
