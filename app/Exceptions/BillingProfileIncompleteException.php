<?php

namespace App\Exceptions;

use RuntimeException;

class BillingProfileIncompleteException extends RuntimeException
{
    /**
     * @param  array<string, string>  $manquants
     */
    public function __construct(
        public readonly array $manquants,
        string $message = 'Le profil de facturation est incomplet.',
    ) {
        parent::__construct($message);
    }
}
