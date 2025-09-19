<?php

namespace App\Utilis;

class Sanitizer
{
    public static function sanitizeText(?string $input): string
    {
        return trim(strip_tags($input ?? ''));
    }
}
