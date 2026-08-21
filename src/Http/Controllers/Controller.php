<?php

namespace App\Http\Controllers;

use App\Models\Partner;

abstract class Controller
{
    /**
     * Partner, jehož produkty se mají zobrazit na veřejném webu.
     *
     * Dotaz je tady, a ne šestkrát v jednotlivých metodách, protože je na
     * všech stránkách stejný. Rozhodnutí, KTERÁ stránka sekci ukazuje, ale
     * zůstává na volajícím — sekce patří jen na část webu (ne do blogu,
     * galerie, vyhledávání a kontaktu), a view composer by tuhle volbu
     * schoval do provideru a navázal ji na názvy views.
     *
     * Příznak je výlučný (viz Partner::booted()), takže first() vrací
     * jediného vybraného partnera, nebo null, když žádný vybraný není.
     */
    protected function activePartner(): ?Partner
    {
        return Partner::published()
            ->where('is_active', true)
            ->first();
    }
}
