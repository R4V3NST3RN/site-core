<?php

namespace App\Support;

/**
 * Výsledek jedné synchronizace kurzu.
 *
 * Tři stavy, ne dva: kromě úspěchu a selhání existuje "přeskočeno"
 * (kurz nemá externí ID, provider je vypnutý). Volající to potřebuje
 * odlišit — přeskočený kurz není chyba a nemá se hlásit jako selhání.
 */
final class SyncResult
{
    private function __construct(
        public readonly bool $succeeded,
        public readonly bool $skipped,
        public readonly ?string $message,
        public readonly ?string $externalId,
    ) {}

    public static function success(?string $externalId = null, ?string $message = null): self
    {
        return new self(true, false, $message, $externalId);
    }

    public static function failure(string $message, ?string $externalId = null): self
    {
        return new self(false, false, $message, $externalId);
    }

    public static function skipped(string $message, ?string $externalId = null): self
    {
        return new self(false, true, $message, $externalId);
    }
}
