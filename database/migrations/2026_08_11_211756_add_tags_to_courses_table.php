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
            // Kurz dostává tagy, ale ZÁMĚRNĚ ne category — sekce je
            // vlastnost článku, kurz má svou sekci implicitně danou tím,
            // že je to kurz.
            $table->json('tags')->nullable()->after('terms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
