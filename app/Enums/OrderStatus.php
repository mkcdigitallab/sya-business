<?php

namespace App\Enums;

/**
 * Le parcours d'une commande, de la demande à la livraison.
 *
 *  Demande reçue → Devis à valider → Commande confirmée → Maquette à valider → En production → Prête → Terminée
 *
 * Aucune étape n'est obligatoire côté atelier (un article standard peut passer directement de « reçue » à
 * « confirmée »), mais on ne revient jamais en arrière.
 */
enum OrderStatus: string
{
    case Received = 'received';
    case Quoted = 'quoted';
    case Confirmed = 'confirmed';
    case Proofing = 'proofing';
    case Production = 'production';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Demande reçue',
            self::Quoted => 'Devis à valider',
            self::Confirmed => 'Commande confirmée',
            self::Proofing => 'Maquette à valider',
            self::Production => 'En production',
            self::Ready => 'Prête',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
        };
    }

    /** Phrase affichée au client sur la page de suivi. */
    public function help(): string
    {
        return match ($this) {
            self::Received => 'Nous étudions votre demande, puis nous confirmons le prix et le délai.',
            self::Quoted => 'Le prix est confirmé. Acceptez le devis pour lancer votre commande.',
            self::Confirmed => 'Votre commande est confirmée : nous préparons la suite.',
            self::Proofing => 'Une maquette vous attend. Validez-la avant que nous lancions la production.',
            self::Production => 'Votre commande est en cours de fabrication.',
            self::Ready => 'Votre commande est prête : retrait ou livraison selon votre choix.',
            self::Completed => 'Commande livrée ou retirée. Merci de votre confiance.',
            self::Cancelled => 'Cette commande a été annulée.',
        };
    }

    /** @return list<self> Étapes que l'atelier peut choisir depuis l'état courant. */
    public function next(): array
    {
        return match ($this) {
            self::Received => [self::Quoted, self::Confirmed, self::Cancelled],
            self::Quoted => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Proofing, self::Production, self::Cancelled],
            self::Proofing => [self::Production, self::Cancelled],
            self::Production => [self::Ready],
            self::Ready => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** Le client peut encore annuler seul tant que rien n'est lancé. */
    public function clientCanCancel(): bool
    {
        return in_array($this, [self::Received, self::Quoted], true);
    }
}
