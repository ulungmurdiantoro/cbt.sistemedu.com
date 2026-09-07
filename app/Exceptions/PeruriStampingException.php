<?php

namespace App\Exceptions;

use Exception;

// Dilempar kalau ada langkah di alur pembubuhan e-meterai Peruri (login,
// generate serial number, atau stamping lewat Sign Adapter) yang gagal.
class PeruriStampingException extends Exception
{
    //
}
