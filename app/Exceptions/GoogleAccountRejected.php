<?php

namespace App\Exceptions;

use RuntimeException;

class GoogleAccountRejected extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = 'Akun Google tidak dapat digunakan untuk masuk sebagai siswa.',
    ) {
        parent::__construct($message);
    }
}
