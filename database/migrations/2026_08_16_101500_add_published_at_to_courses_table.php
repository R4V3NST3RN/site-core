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
            // Datum publikace řídí veřejnou viditelnost společně se statusem:
            // kurz je na webu vidět, jen když published_at už nastalo. Kurz
            // ho potřebuje ze stejného důvodu jako článek — kvůli řazení
            // příspěvků a kvůli plánované publikaci.
            //
            // NULL znamená neveřejný, ZÁMĚRNĚ bez fallbacku na created_at
            // v dotazech: jinak by "nepublikováno" byly dva různé stavy
            // (NULL a budoucí datum), které se chovají jinak.
            $table->timestamp('published_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
