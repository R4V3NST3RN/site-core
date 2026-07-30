<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B2 (P1): sloupec nesmí jmenovat konkrétní integraci.
 *
 * Sdílené jádro se od B2 baví jen s App\Contracts\CourseSyncProvider,
 * takže i sloupec je nově neutrální — 'external_sync_id'. Unique index
 * se přejmenovává s ním, aby v schématu nezůstalo jméno klienta.
 *
 * Data se nepřepisují, jde čistě o přejmenování (ALTER TABLE RENAME
 * COLUMN), takže obě strany migrace jsou beze ztráty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('isport_id', 'external_sync_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_isport_id_unique');
            $table->unique('external_sync_id');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_external_sync_id_unique');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('external_sync_id', 'isport_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('isport_id');
        });
    }
};
