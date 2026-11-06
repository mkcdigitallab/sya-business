<?php

namespace App\Support;

/**
 * Lien de suivi sans compte : HMAC-SHA256 de la référence de commande, en base64url (43 caractères).
 * Rien n'est stocké : le serveur recalcule le jeton et le compare.
 */
class TrackingTokens
{
    public const REFERENCE = '/^SY-\d{6}-[A-Z0-9]{5}$/';

    public const TOKEN = '/^[A-Za-z0-9_-]{43}$/';

    private static function secret(): string
    {
        $configured = config('siya.tracking_secret');

        return filled($configured)
            ? (string) $configured
            : hash_hmac('sha256', 'siya-tracking', (string) config('app.key'));
    }

    public static function forOrder(string $reference): string
    {
        $binary = hash_hmac('sha256', 'track:'.$reference, self::secret(), true);

        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function verify(?string $reference, ?string $token): bool
    {
        if (! preg_match(self::REFERENCE, (string) $reference) || ! preg_match(self::TOKEN, (string) $token)) {
            return false;
        }

        return hash_equals(self::forOrder((string) $reference), (string) $token);
    }
}
