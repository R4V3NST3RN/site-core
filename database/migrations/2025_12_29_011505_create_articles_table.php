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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Titulek článku
            $table->text('slug')->unique(); // URL-friendly název (např. "muj-clanek")
            $table->text('perex')->nullable(); // Úvodní text (perex)
            $table->longText('content'); // Hlavní obsah článku
            $table->string('featured_image')->nullable(); // Hlavní obrázek
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Autor článku
            $table->enum('status', ['draft', 'published'])->default('draft'); // Stav: koncept nebo publikováno
            $table->timestamp('published_at')->nullable(); // Datum publikace
            $table->softDeletes(); // Pro soft delete (měkké mazání)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
