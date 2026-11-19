<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidQrSignatureException extends Exception
{
    public function __construct(string $message = 'Tanda tangan kriptografi QR tidak valid. Tiket palsu atau telah dimodifikasi.')
    {
        parent::__construct($message, 400);
    }
}
