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
        Schema::table('course_types', function (Blueprint $table) {
            // Portrét pro výpis typů kurzů — samostatný sloupec vedle
            // hero_image, protože jde o jiný ořez (3:4 proti 16:9)
            // a jednou fotkou se obojí uspokojivě obsloužit nedá.
            $table->string('list_image')->nullable()->after('hero_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_types', function (Blueprint $table) {
            $table->dropColumn('list_image');
        });
    }
};
