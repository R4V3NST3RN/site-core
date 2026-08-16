<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nasazení filtru published_at <= now() by bez tohohle kroku skrylo
        // všechen existující obsah, který datum publikace nikdy nedostal —
        // u článků je sloupec nepovinný odjakživa, u kurzů vzniká teprve
        // předchozí migrací.
        //
        // Zápis jde přes query builder, NE přes Eloquent: modely mají casty,
        // boot hooky (HasTags) a soft delete scope, které by se sem promítly.
        // Migrace musí popisovat stav DB nezávisle na tom, jak modely zrovna
        // vypadají — a taky přežít to, že se model v budoucnu změní.
        //
        // Bez podmínky na deleted_at schválně: soft-smazaný záznam se může
        // vrátit a má mít datum jako ostatní.
        //
        // Řádek s created_at IS NULL zůstane nedotčený, a tím pádem neveřejný.
        // To je konzistentní s pravidlem "NULL published_at = skryto".
        foreach (['articles', 'courses'] as $table) {
            DB::table($table)
                ->whereNull('published_at')
                ->update(['published_at' => DB::raw('created_at')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Záměrně prázdné — data se nevrací.
        //
        // Migrace si nikde nepamatuje, které řádky doplnila, takže jediný
        // možný rollback by byl "vynuluj published_at všude". Tím by ale
        // smazal i data, která mezitím vyplnil redaktor ručně, a skryl
        // obsah, který byl předtím veřejný. Prázdný down() je bezpečnější
        // než rollback, který ničí obsah.
        //
        // Schéma vrací zpět předchozí migrace (drop sloupce na courses),
        // takže rollback páru jako celku není zablokovaný.
    }
};
