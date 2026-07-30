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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            // Propojení
        $table->foreignId('course_type_id')->constrained()->onDelete('cascade'); // Typ kurzu
        $table->foreignId('trainer_id')->constrained()->onDelete('cascade'); // Trenér

        // Termín kurzu
        $table->string('day_of_week'); // Den v týdnu (např. "úterý")
        $table->string('time_of_day'); // Denní doba (např. "večer", "ráno")
        $table->time('start_time'); // Začátek (např. 19:00)
        $table->time('end_time'); // Konec (např. 20:00)
        $table->string('months'); // Měsíce (např. "listopad - únor")
        $table->integer('year'); // Rok ukončení (např. 2026)

        // Data kurzů
        $table->text('description')->nullable(); // Popis kurzu
        $table->integer('total_lessons')->default(0); // Celkový počet lekcí
        $table->integer('remaining_lessons')->nullable(); // Zbývající lekce
        $table->decimal('price_per_course', 10, 2)->nullable(); // Cena za celý kurz
        $table->decimal('price_per_lesson', 10, 2)->nullable(); // Cena za jednu lekci
        $table->integer('available_spots')->default(0); // Volná místa
        $table->integer('total_spots')->default(0); // Celková kapacita

        // iSportSystem integrace
        $table->string('isport_id')->nullable()->unique(); // ID v iSportSystem
        $table->timestamp('last_synced_at')->nullable(); // Poslední synchronizace

        // Auto-generované
        $table->string('slug')->unique(); // URL slug
        $table->text('auto_title')->nullable(); // Auto-generovaný název

        // Stavy
        $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
        $table->boolean('is_featured')->default(false); // Zvýrazněný kurz
        
        $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
