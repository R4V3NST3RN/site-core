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
        Schema::create('course_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Název typu (např. "Gymnastika pro děti 6-10 let")
            $table->string('slug')->unique(); // URL slug
            $table->text('description')->nullable(); // Základní popis
            $table->string('age_category')->nullable(); // Věková kategorie (např. "6-10 let") - jen pro děti
            $table->boolean('is_for_children')->default(false); // Je pro děti?
            $table->boolean('is_active')->default(true); // Je aktivní?
            $table->integer('order')->default(0); // Pořadí zobrazení
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_types');
    }
};
