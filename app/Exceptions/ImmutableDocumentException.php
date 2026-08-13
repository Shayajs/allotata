<?php

namespace App\Exceptions;

use RuntimeException;

class ImmutableDocumentException extends RuntimeException
{
    public function __construct(string $message = 'Ce document est verrouillé et ne peut plus être modifié.')
    {
        parent::__construct($message);
    }
}
