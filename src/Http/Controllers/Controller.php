<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Partner;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Aktivní trenéři v pořadí, v jakém se mají zobrazit.
     *
     * Stejná úvaha jako u activePartner(): dotaz je společný, volba stránky
     * zůstává na volajícím. Podmínku i řazení drží Trainer::activeOrdered(),
     * ať se výpis nerozejde s hlavičkou, která se ptá na totéž.
     *
     * $exceptId vynechá jednoho trenéra — na jeho vlastním detailu, aby se
     * ve výpisu neopakoval ten, jehož profil návštěvník právě čte. Bez
     * vyplněného id se nevynechává nikdo.
     *
     * @return Collection<int, Trainer>
     */
    protected function allTrainers(?int $exceptId = null): Collection
    {
        return Trainer::activeOrdered()
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->get();
    }

    /**
     * Galerie vybraná pro titulku — a nově i pro další stránky, které
     * sdílený blok sekcí zobrazují.
     *
     * Příznak je výlučný (viz Gallery::booted()), takže first() vrací
     * jedinou vybranou galerii, nebo null, když žádná vybraná není.
     * published() tu musí být: samotný příznak o viditelnosti nic neříká,
     * bez něj by se na web dostal koncept.
     */
    protected function homepageGallery(): ?Gallery
    {
        return Gallery::published()
            ->where('show_on_homepage', true)
            ->first();
    }
}
