<?php

namespace App\Enums;

/**
 * Stav přihlašování na kurz, odvozený z dat — ne ručně nastavovaný sloupec.
 *
 * Odvozuje se v Course::getEnrollmentStateAttribute() z budoucích termínů
 * a z available_spots. Protože available_spots plní sync z externího systému,
 * vrací odhlášení účastníka kurz z Full do Open samo, bez dalšího zásahu.
 *
 * Unknown je záměrně samostatná hodnota, ne příznak vedle ostatních tří:
 * detail kurzu, badge u nadpisu i budoucí výpis kurzů o něm potřebují vědět
 * stejně jako o Open/Full/Finished, a hodnota enumu je jediné místo, které
 * to zaručí — kdyby to byla podmínka navíc (`blank($course->terms)`), musel
 * by ji zopakovat každý spotřebitel zvlášť a časem by se snadno na jednom
 * z míst zapomnělo (přesně tenhle bug řeší tahle oprava u badge).
 *
 * Popisky tady záměrně nejsou: "Kurz je plně obsazený" vs. "Výcvik je plně
 * obsazený" je per-web copy a patří do Blade, ne do sdíleného jádra.
 */
enum CourseEnrollmentState: string
{
    /** Žádná data o termínech (terms prázdné/null) — kurz čeká na první sync. */
    case Unknown = 'unknown';

    /** Jsou budoucí termíny a je kam přihlásit. */
    case Open = 'open';

    /** Jsou budoucí termíny, ale kapacita je vyčerpaná. */
    case Full = 'full';

    /** Žádný budoucí termín — kurz doběhl. */
    case Finished = 'finished';
}
