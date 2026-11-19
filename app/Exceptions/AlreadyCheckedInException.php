<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class AlreadyCheckedInException extends Exception
{
    public function __construct(string $message = 'Tiket ini sudah pernah dipindai sebelumnya pada presensi hari-H.')
    {
        parent::__construct($message, 409);
    }
}
