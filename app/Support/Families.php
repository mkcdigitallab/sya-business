<?php

namespace App\Support;

/**
 * Les grandes familles de l'offre. C'est ce qui structure la navigation du site :
 * accueil → famille → produit/service. Pour en ajouter une, ajoutez une entrée ici
 * et rattachez des produits à son `slug`.
 */
final class Families
{
    /** @return list<array{slug:string,label:string,short:string,tagline:string,description:string,icon:string}> */
    public static function all(): array
    {
        return [
            ['slug' => 'maillots', 'label' => 'Maillots & équipes', 'short' => 'Maillots', 'icon' => 'shirt',
                'tagline' => 'Maillots, flocage, numéros',
                'description' => 'Équipez votre équipe, votre club ou votre association : maillots, flocage du nom et du numéro, impression de numéros.'],
            ['slug' => 'textile', 'label' => 'Textile personnalisé', 'short' => 'Textile', 'icon' => 'scissors',
                'tagline' => 'T-shirts, polos, gilets, sérigraphie',
                'description' => 'T-shirts, polos type Lacoste, gilets et sérigraphie en série pour vos équipes, événements et entreprises.'],
            ['slug' => 'impression', 'label' => 'Impression & signalétique', 'short' => 'Impression', 'icon' => 'printer',
                'tagline' => 'Bâches, vinyle, affiches, cartes de visite',
                'description' => 'Bâches grand format, vinyle, affiches et cartes de visite pour faire voir votre activité.'],
            ['slug' => 'creation', 'label' => 'Création graphique', 'short' => 'Création', 'icon' => 'pen',
                'tagline' => 'Infographie, logo, visuels',
                'description' => 'Infographie, logo, identité visuelle et supports de communication : nous dessinons ce que nous imprimons.'],
            ['slug' => 'mode', 'label' => 'Mode & accessoires', 'short' => 'Mode', 'icon' => 'bag',
                'tagline' => 'Baskets, sacs, vêtements',
                'description' => 'Une sélection de vêtements et accessoires du quotidien, disponibles à la boutique.'],
        ];
    }

    public static function find(?string $slug): ?array
    {
        foreach (self::all() as $family) {
            if ($family['slug'] === $slug) {
                return $family;
            }
        }

        return null;
    }

    public static function label(?string $slug): string
    {
        return self::find($slug)['label'] ?? (string) $slug;
    }
}
