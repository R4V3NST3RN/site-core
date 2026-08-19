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
        Schema::create('course_type_gallery', function (Blueprint $table) {
            $table->foreignId('course_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();

            // Dvojice smí být v tabulce jen jednou — bez toho by opakované
            // uložení formuláře přidalo tutéž vazbu podruhé.
            $table->unique(['course_type_id', 'gallery_id']);

            // Bez timestamps: pivot nese jen vazbu, nic, co by šlo datovat.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_type_gallery');
    }
};
