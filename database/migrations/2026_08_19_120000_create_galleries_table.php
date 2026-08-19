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
        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Název galerie
            // Index, ne unique: galerie mají softDeletes, takže smazaný
            // záznam drží slug dál a unique by bránil založit galerii
            // se stejným názvem znovu. Vyhledává se přes něj v show().
            $table->string('slug')->index(); // URL-friendly název
            $table->text('description')->nullable(); // Popis galerie
            $table->string('cover_image')->nullable(); // Náhled do výpisu
            $table->json('photos')->nullable(); // Fotky [{image, caption}]
            $table->boolean('show_on_homepage')->default(false); // Výlučný příznak, viz Gallery::booted()
            $table->string('status')->default('draft'); // Stav: draft nebo published
            $table->timestamp('published_at')->nullable(); // Datum publikace
            $table->softDeletes(); // Měkké mazání
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};
