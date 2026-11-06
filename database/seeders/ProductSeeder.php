<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Catalogue de départ. Les PRIX SONT INDICATIFS : ce sont ceux de l'ancienne version du projet, à valider par
 * Siya Business (ils se modifient ensuite depuis /admin/produits). Un prix `null` signifie « sur devis ».
 *
 * Lecture des options (colonne `options`) :
 *   sizes / size_label   liste de tailles proposées
 *   roster               liste de joueurs : fields = colonnes saisies, required = colonnes obligatoires
 *   extras               cases à cocher avec supplément (locked = toujours incluse)
 *   choices              choix unique (format, recto/verso) avec supplément éventuel
 *   dimensions           largeur/hauteur en cm (produits vendus au m²)
 *   pack_size            nombre d'exemplaires par pack
 *   needs_artwork        invite le client à envoyer son logo ou son visuel
 *   note_hint            exemple affiché dans le champ « précisions »
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'slug' => 'maillot-personnalise', 'name' => 'Maillot de sport', 'family' => 'maillots',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 8500,
                'description' => 'Maillot pour équipes, clubs, associations et événements. Choisissez la taille ; la couleur et le modèle se précisent dans la note, et nous confirmons la disponibilité.',
                'image_url' => 'https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=900&q=85',
                'is_featured' => true, 'sort_order' => 1,
                'options' => [
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'size_label' => 'Taille',
                    'note_hint' => 'Couleur, modèle, club… (nous confirmons la disponibilité)',
                ],
            ],
            [
                'slug' => 'maillot-flocage', 'name' => 'Maillot + flocage', 'family' => 'maillots',
                'pricing_mode' => 'unit', 'unit_label' => 'maillot floqué', 'price' => 8500,
                'description' => 'Le pack équipe : maillot et flocage du nom et du numéro de chaque joueur. Saisissez votre liste (nom, numéro, taille) et nous nous occupons du reste.',
                'image_url' => null,
                'is_featured' => true, 'sort_order' => 2,
                'options' => [
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'roster' => [
                        'fields' => ['name', 'number', 'size'],
                        'required' => ['number', 'size'],
                    ],
                    'extras' => [
                        [
                            'key' => 'flocage',
                            'label' => 'Flocage nom & numéro',
                            'price' => 2500,
                            'per' => 'unit',
                            'locked' => true,
                        ],
                    ],
                    'needs_artwork' => true,
                    'note_hint' => 'Couleurs du maillot, logo du club, emplacement du flocage…',
                ],
            ],
            [
                'slug' => 'flocage-nom-numero', 'name' => 'Flocage nom & numéro', 'family' => 'maillots',
                'pricing_mode' => 'unit', 'unit_label' => 'ligne floquée', 'price' => 2500,
                'description' => 'Vous avez déjà vos maillots ? Nous floquons le nom et le numéro de chacun. Saisissez votre liste, une ligne par joueur.',
                'image_url' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 3,
                'options' => [
                    'roster' => [
                        'fields' => ['name', 'number'],
                        'required' => ['number'],
                    ],
                    'needs_artwork' => false,
                    'note_hint' => 'Couleur du flocage, police souhaitée…',
                ],
            ],
            [
                'slug' => 'impression-numeros', 'name' => 'Impression de numéros', 'family' => 'maillots',
                'pricing_mode' => 'quote', 'unit_label' => 'numéro', 'price' => null,
                'description' => 'Impression de numéros sur vos maillots et tenues. Indiquez la liste des numéros : nous vous répondons avec le prix.',
                'image_url' => null,
                'is_featured' => false, 'sort_order' => 4,
                'options' => [
                    'roster' => [
                        'fields' => ['number'],
                        'required' => ['number'],
                    ],
                    'note_hint' => 'Taille et couleur des numéros, emplacement…',
                ],
            ],
            [
                'slug' => 't-shirt-personnalise', 'name' => 'T-shirt personnalisé', 'family' => 'textile',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 5000,
                'description' => 'T-shirts personnalisés pour votre marque, votre association ou votre événement. Envoyez votre logo ou votre visuel avec la commande.',
                'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=85',
                'is_featured' => true, 'sort_order' => 1,
                'options' => [
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'needs_artwork' => true,
                    'note_hint' => 'Couleur du T-shirt, emplacement de l’impression…',
                ],
            ],
            [
                'slug' => 'polo-pique-personnalise', 'name' => 'Polo (type Lacoste) personnalisé', 'family' => 'textile',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 9000,
                'description' => 'Polo en piqué de style Lacoste, à personnaliser avec votre logo ou votre nom pour vos équipes et vos clients.',
                'image_url' => 'https://images.unsplash.com/photo-1625910513413-5fc3c7e7c5e2?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 2,
                'options' => [
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'needs_artwork' => true,
                    'note_hint' => 'Couleur du polo, broderie ou impression, emplacement…',
                ],
            ],
            [
                'slug' => 'gilet-personnalise', 'name' => 'Gilet personnalisé', 'family' => 'textile',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 7500,
                'description' => 'Gilets personnalisés pour vos équipes de terrain, vos événements et vos entreprises.',
                'image_url' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 3,
                'options' => [
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'needs_artwork' => true,
                    'note_hint' => 'Couleur du gilet, logo devant ou dans le dos…',
                ],
            ],
            [
                'slug' => 'serigraphie-textile', 'name' => 'Sérigraphie textile', 'family' => 'textile',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce imprimée', 'price' => 3500,
                'description' => 'Impression en série par sérigraphie sur vos textiles. Idéal dès que vous avez plusieurs pièces à imprimer avec le même visuel.',
                'image_url' => 'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 4,
                'options' => [
                    'needs_artwork' => true,
                    'note_hint' => 'Nombre de couleurs, emplacement de l’impression, type de textile…',
                ],
            ],
            [
                'slug' => 'bache-publicitaire', 'name' => 'Bâche publicitaire', 'family' => 'impression',
                'pricing_mode' => 'area', 'unit_label' => 'm²', 'price' => 12000,
                'description' => 'Bâches grand format pour enseignes, façades et événements. Indiquez les dimensions : le prix est calculé au mètre carré.',
                'image_url' => 'https://images.unsplash.com/photo-1557683316-973673baf926?auto=format&fit=crop&w=900&q=85',
                'is_featured' => true, 'sort_order' => 1,
                'options' => [
                    'dimensions' => [
                        'min_cm' => 20,
                        'max_cm' => 1000,
                    ],
                    'needs_artwork' => true,
                    'note_hint' => 'Œillets, finition, lieu de pose…',
                ],
            ],
            [
                'slug' => 'impression-vinyle', 'name' => 'Impression vinyle', 'family' => 'impression',
                'pricing_mode' => 'area', 'unit_label' => 'm²', 'price' => 10000,
                'description' => 'Vinyle pour vitrines, véhicules et panneaux. Indiquez les dimensions : le prix est calculé au mètre carré.',
                'image_url' => 'https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 2,
                'options' => [
                    'dimensions' => [
                        'min_cm' => 10,
                        'max_cm' => 1000,
                    ],
                    'needs_artwork' => true,
                    'note_hint' => 'Surface de pose, découpe, vinyle brillant ou mat…',
                ],
            ],
            [
                'slug' => 'affiche-publicitaire', 'name' => 'Affiche publicitaire', 'family' => 'impression',
                'pricing_mode' => 'unit', 'unit_label' => 'affiche', 'price' => 3000,
                'description' => 'Affiches pour vos offres, vos événements et vos annonces. Choisissez le format ; nous confirmons le prix exact selon le format.',
                'image_url' => 'https://images.unsplash.com/photo-1509223197845-458d87318791?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 3,
                'options' => [
                    'choices' => [
                        [
                            'key' => 'format',
                            'label' => 'Format',
                            'values' => [
                                [
                                    'value' => 'A3',
                                    'label' => 'A3',
                                ],
                                [
                                    'value' => 'A2',
                                    'label' => 'A2',
                                ],
                                [
                                    'value' => 'A1',
                                    'label' => 'A1',
                                ],
                                [
                                    'value' => 'autre',
                                    'label' => 'Autre format (à préciser)',
                                ],
                            ],
                        ],
                    ],
                    'needs_artwork' => true,
                    'note_hint' => 'Papier souhaité, texte à imprimer…',
                ],
            ],
            [
                'slug' => 'cartes-de-visite', 'name' => 'Cartes de visite', 'family' => 'impression',
                'pricing_mode' => 'pack', 'unit_label' => 'pack de 100', 'price' => 5000,
                'description' => 'Cartes de visite soignées pour votre activité. Chaque pack contient 100 cartes.',
                'image_url' => 'https://images.unsplash.com/photo-1589330694653-ded6df03f754?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 4,
                'options' => [
                    'pack_size' => 100,
                    'choices' => [
                        [
                            'key' => 'faces',
                            'label' => 'Impression',
                            'values' => [
                                [
                                    'value' => 'recto',
                                    'label' => 'Recto',
                                ],
                                [
                                    'value' => 'recto_verso',
                                    'label' => 'Recto-verso',
                                ],
                            ],
                        ],
                    ],
                    'needs_artwork' => true,
                    'note_hint' => 'Nom, fonction, téléphone, adresse… ou envoyez votre fichier',
                ],
            ],
            [
                'slug' => 'infographie', 'name' => 'Infographie', 'family' => 'creation',
                'pricing_mode' => 'quote', 'unit_label' => 'projet', 'price' => null,
                'description' => 'Transformez vos informations en visuels clairs : infographies, schémas, présentations et supports pédagogiques ou commerciaux. Décrivez votre besoin, nous vous répondons avec un devis.',
                'image_url' => null,
                'is_featured' => false, 'sort_order' => 1,
                'options' => [
                    'needs_artwork' => false,
                    'note_hint' => 'Sujet, nombre de visuels, format, date souhaitée…',
                ],
            ],
            [
                'slug' => 'design-logo', 'name' => 'Logo & identité visuelle', 'family' => 'creation',
                'pricing_mode' => 'quote', 'unit_label' => 'projet', 'price' => null,
                'description' => 'Création de votre logo et de votre identité visuelle : couleurs, typographies et déclinaisons pour vos supports. Décrivez votre activité, nous vous répondons avec un devis.',
                'image_url' => null,
                'is_featured' => true, 'sort_order' => 2,
                'options' => [
                    'needs_artwork' => false,
                    'note_hint' => 'Nom de l’activité, style souhaité, exemples qui vous plaisent…',
                ],
            ],
            [
                'slug' => 'creation-graphique', 'name' => 'Création de visuels', 'family' => 'creation',
                'pricing_mode' => 'quote', 'unit_label' => 'visuel', 'price' => 5000,
                'description' => 'Flyers, visuels pour les réseaux sociaux, affiches et supports de communication. Le prix de départ est indicatif : le devis dépend du projet.',
                'image_url' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 3,
                'options' => [
                    'needs_artwork' => false,
                    'note_hint' => 'Support, texte, couleurs, date de l’événement…',
                ],
            ],
            [
                'slug' => 'baskets-urbaines', 'name' => 'Baskets urbaines', 'family' => 'mode',
                'pricing_mode' => 'unit', 'unit_label' => 'paire', 'price' => 18000,
                'description' => 'Baskets au style urbain pour accompagner vos tenues du quotidien. Pointure à confirmer selon la disponibilité.',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 1,
                'options' => [
                    'sizes' => ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
                    'size_label' => 'Pointure',
                    'note_hint' => 'Couleur souhaitée',
                ],
            ],
            [
                'slug' => 'baskets-classiques', 'name' => 'Baskets classiques', 'family' => 'mode',
                'pricing_mode' => 'unit', 'unit_label' => 'paire', 'price' => 16000,
                'description' => 'Modèle polyvalent à porter au quotidien. Pointure à confirmer selon la disponibilité.',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 2,
                'options' => [
                    'sizes' => ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
                    'size_label' => 'Pointure',
                    'note_hint' => 'Couleur souhaitée',
                ],
            ],
            [
                'slug' => 'sac-de-ville', 'name' => 'Sac de ville', 'family' => 'mode',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 12000,
                'description' => 'Sac pratique pour les sorties et les déplacements.',
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 3,
                'options' => [
                    'note_hint' => 'Couleur souhaitée',
                ],
            ],
            [
                'slug' => 'jean-classique', 'name' => 'Jean classique', 'family' => 'mode',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 15000,
                'description' => 'Jean à porter au quotidien. Taille et coloris à confirmer selon la disponibilité.',
                'image_url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 4,
                'options' => [
                    'sizes' => ['28', '30', '32', '34', '36', '38', '40'],
                    'size_label' => 'Taille',
                    'note_hint' => 'Coloris souhaité',
                ],
            ],
            [
                'slug' => 'chemise-casual', 'name' => 'Chemise casual', 'family' => 'mode',
                'pricing_mode' => 'unit', 'unit_label' => 'pièce', 'price' => 12000,
                'description' => 'Chemise pour un style décontracté ou habillé.',
                'image_url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=900&q=85',
                'is_featured' => false, 'sort_order' => 5,
                'options' => [
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'note_hint' => 'Coloris souhaité',
                ],
            ],
        ];

        foreach ($products as $product) {
            // updateOrCreate sur le slug : relancer le seeder met à jour le catalogue sans doublons.
            Product::updateOrCreate(['slug' => $product['slug']], $product);
        }
    }
}
