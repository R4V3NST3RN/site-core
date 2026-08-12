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
            // Kurz záměrně nedostává 'template' ani 'category' — redakční
            // šablony a rubriky jsou vlastnost článku, kurz má blokový
            // obsah jen jako rozšíření svého popisu.
            $table->json('blocks')->nullable()->after('description');
            $table->text('perex')->nullable()->after('blocks');
            $table->string('featured_image')->nullable()->after('perex');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['blocks', 'perex', 'featured_image']);
        });
    }
};
