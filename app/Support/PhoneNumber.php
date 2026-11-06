<?php

namespace App\Support;

/** Numéros sénégalais : +221 suivi de 9 chiffres commençant par 3 ou 7. */
class PhoneNumber
{
    public static function normalize(?string $value): string
    {
        $normalized = preg_replace('/[\s().\-]/', '', trim((string) $value)) ?? '';

        if (str_starts_with($normalized, '00221')) {
            return '+221'.substr($normalized, 5);
        }
        if (str_starts_with($normalized, '221') && strlen($normalized) === 12) {
            return '+'.$normalized;
        }
        if (preg_match('/^[37]\d{8}$/', $normalized)) {
            return '+221'.$normalized;
        }

        return $normalized;
    }

    public static function isValid(?string $value): bool
    {
        return (bool) preg_match('/^(?:\+221)?[37]\d{8}$/', self::normalize($value));
    }
}
