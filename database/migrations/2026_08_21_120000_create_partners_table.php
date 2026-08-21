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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Název partnera
            // Index, ne unique: partneři mají softDeletes, takže smazaný
            // záznam drží slug dál a unique by bránil založit partnera
            // se stejným názvem znovu. Stejná úvaha jako u galleries.
            $table->string('slug')->index(); // URL-friendly název
            $table->string('url')->nullable(); // Odkaz na e-shop partnera
            $table->json('products')->nullable(); // Produkty [{image, title}]
            $table->boolean('is_active')->default(false); // Výlučný příznak, viz Partner::booted()
            $table->integer('order')->default(0); // Řazení, až bude partnerů víc
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
        Schema::dropIfExists('partners');
    }
};
