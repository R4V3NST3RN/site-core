<?php

namespace App\Enums;

/**
 * Důvod kontaktního dotazu (N5). Klíče jsou sdílené jádro a nesmí se
 * měnit bez migrace dat — popisky (per-web obsah) jsou v lang/cs/contact.php
 * pod stejnými klíči.
 */
enum ContactReason: string
{
    case Signup = 'signup';
    case Question = 'question';
    case Changes = 'changes';
    case Meeting = 'meeting';
    case Other = 'other';

    public function label(): string
    {
        return __('contact.reasons.'.$this->value);
    }
}
