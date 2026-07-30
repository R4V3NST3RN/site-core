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
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Jméno
            $table->string('last_name'); // Příjmení
            $table->string('photo')->nullable(); // Fotka
            $table->text('bio')->nullable(); // Popis/Bio
            $table->string('specialization')->nullable(); // Specializace
            $table->string('email')->nullable(); // Email
            $table->string('phone')->nullable(); // Telefon
            $table->boolean('is_active')->default(true); // Je aktivní?
            $table->integer('order')->default(0); // Pořadí zobrazení
            $table->softDeletes(); // Měkké mazání
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
