# Siya Business — boutique d'impression, de textile et de création

Site de vente et de prise de commande pour **Siya Business** : maillots, flocage, textile personnalisé, sérigraphie, bâches, vinyle,
affiches, cartes de visite, infographie et création graphique.
Stack **VILT** : Vue 3 · Inertia 2 · Laravel 12 · Tailwind 3.

> Le point de départ du projet, c'est qu'on ne vend pas ici des articles « tout faits » : on prend une **demande**, on **confirme le prix**,
> on fait **valider la maquette**, puis on produit. Tout le site est construit autour de ce parcours.

## Plan du site

### Pages publiques (sans compte)

| Page | Adresse | Rôle |
|---|---|---|
| Accueil | `/` | Présenter l'offre en 5 familles, le parcours en 4 étapes, les produits populaires, l'appel à l'action « équipe » |
| Boutique | `/boutique` | Catalogue, filtré par famille (`?famille=maillots`) et par recherche (`?q=bâche`) |
| Fiche produit | `/produit/{slug}` | **Configurateur** : taille, liste de joueurs, dimensions, options, quantité, fichiers à venir ; estimation du prix en direct |
| Panier | `/panier` | Plusieurs lignes, y compris le même produit avec des personnalisations différentes |
| Commande | `/commande` | Coordonnées, retrait ou livraison, fichiers (logo, visuel), moyen d'acompte. **Aucun paiement à ce stade** |
| Suivi | `/suivi/{référence}?token=…` | Frise d'avancement, **accepter le devis**, **valider ou refuser une maquette**, paiements, historique, annulation |
| Devis | `/devis` | Demande libre (infographie, logo, grand format, grosses quantités) |
| Contact | `/contact` | Téléphone et message |
| Informations | `/informations` | Comment ça marche, acompte, maquette, annulation, questions fréquentes |

### Administration (`/admin`, connexion obligatoire)

| Page | Adresse | Rôle |
|---|---|---|
| Tableau de bord | `/admin` | Une carte par action attendue : demandes à traiter, devis envoyés, maquettes en attente, à lancer, en production, prêtes |
| Commandes | `/admin/commandes` | Liste filtrable par statut et recherchable (référence, nom, téléphone) |
| Détail d'une commande | `/admin/commandes/{référence}` | Articles et listes de joueurs, fichiers du client, **fixer le prix et envoyer le devis**, **envoyer une maquette**, enregistrer un paiement, changer d'étape, notes internes, historique |
| Demandes | `/admin/demandes` | Devis et messages de contact : à rappeler, rappelée, clôturée |
| Catalogue | `/admin/produits` | Prix, visibilité, photo, tailles, ordre d'affichage |

## Le parcours d'une commande

```
Demande reçue ──► Devis à valider ──► Commande confirmée ──► Maquette à valider ──► En production ──► Prête ──► Terminée
   (client)         (atelier fixe        (client accepte        (atelier envoie,       (rien n'est         (retrait ou
                     le prix)             le devis + acompte)    client valide)         imprimé avant)      livraison)
```

- **Aucune étape n'est obligatoire** : un article au prix fixe peut aller de « reçue » à « confirmée » en un clic. « Devis » et « Maquette » n'apparaissent
  dans le suivi du client que si la commande les utilise.
- **On ne revient jamais en arrière.** Les règles vivent dans un seul fichier : `app/Services/OrderWorkflow.php`.
- **La production est bloquée** tant qu'une maquette n'est pas validée (sauf accord reçu autrement, case à cocher, tracé dans l'historique).
- Une commande qui contient un article **sur devis** ne peut pas être confirmée avant qu'un prix soit fixé.
- Le client peut annuler seul tant que la commande n'est pas confirmée ; ensuite, il appelle.
- **Paiement** : acompte de 50 % (réglable) à la confirmation, solde à la remise. Les paiements sont **enregistrés par l'atelier**
  (espèces, Wave, Orange Money, virement) ; il n'y a pas de paiement en ligne automatique.

## Comment les prix sont calculés

Le navigateur ne décide jamais d'un prix : le serveur recalcule tout (`app/Services/ProductConfigurator.php`). Quatre modes :

| Mode | Calcul | Exemple |
|---|---|---|
| `unit` | prix × quantité (+ options) | T-shirt, polo, gilet, maillot, flocage |
| `area` | prix du m² × surface × quantité | Bâche, vinyle : 200 × 100 cm = 2 m² |
| `pack` | prix du pack × nombre de packs | Cartes de visite : 1 pack = 100 cartes |
| `quote` | pas de prix automatique : **sur devis** | Infographie, logo, impression de numéros |

**Maillot + flocage** : on saisit une liste *nom / numéro / taille*, une ligne par joueur ; le prix est (maillot + flocage) × nombre de lignes.
Les options (extras, choix, tailles, dimensions, liste) se décrivent dans la colonne `products.options` ; des exemples commentés sont dans
`database/seeders/ProductSeeder.php`.

## Direction artistique

Reprise du **logo** (badge doré « SB », texte brun très foncé), à la place de l'ancienne palette bleu marine.

- **Or** `#C9A346` (couleur de marque) · **ivoire** `#FAF7F0` (fond, comme un papier d'imprimerie) · **noir chaud** `#1C1710` (texte, pied de page).
  L'or foncé `#8A6A1B` sert au texte sur fond clair, pour rester lisible.
- **Motifs d'imprimerie** : repères de coupe aux coins des visuels, trame de demi-teinte discrète, quatre pastilles cyan-magenta-jaune-noir dans le pied de page.
- **Polices** : Manrope (titres) et DM Sans (texte).
- Tout est défini dans `tailwind.config.js` et `resources/css/app.css`.

## Organisation du code

```
app/
  Enums/                OrderStatus (étapes + libellés) · PaymentMethod
  Models/               Product · Order · OrderItem · OrderFile · OrderEvent · Payment · Inquiry · User
  Services/             ProductConfigurator (prix) · CartService · OrderService · OrderWorkflow (règles de statut) · OrderFiles
  Http/Controllers/
    Store/              pages publiques
    Admin/              administration
  Support/              Families (les 5 familles) · OrderPresenter · CartPresenter · TrackingTokens · PhoneNumber
config/siya.php         réglages métier (téléphone, acompte, limites)
database/               migrations + ProductSeeder (20 produits et services)
resources/js/
  Pages/Store/          9 pages publiques        Pages/Admin/   7 pages d'administration
  Components/           ProductCard, RosterEditor, FileDropzone, Timeline, Icon…
  Layouts/              StoreLayout · AdminLayout
tests/                  46 tests (prix, panier, commande, suivi, workflow, administration)
```

## Installation

Prérequis : PHP 8.2+, Composer, Node 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed     # ⚠ efface la base locale : le schéma a été refait
php artisan storage:link             # rend les photos de produits accessibles
npm install
npm run build                        # ou « npm run dev » pour développer
php artisan serve
```

Créer le compte de l'atelier (le mot de passe est demandé de façon masquée, 12 caractères minimum) :

```bash
php artisan siya:admin votre@email.com --name="Votre nom"
```

Puis ouvrir **`/admin`**. La même commande réinitialise un mot de passe oublié.

## Réglages (`.env`)

| Variable | Rôle |
|---|---|
| `SIYA_PHONE_NUMBER` | Téléphone affiché partout (appel uniquement) |
| `SIYA_EMAIL`, `SIYA_ADDRESS`, `SIYA_HOURS` | Contact : **n'apparaît que si renseigné** |
| `SIYA_INSTAGRAM`, `SIYA_FACEBOOK`, `SIYA_TIKTOK` | Réseaux sociaux : idem, rien n'est inventé |
| `SIYA_PAYMENT_NUMBER` | Numéro Wave / Orange Money indiqué pour l'acompte |
| `SIYA_DEPOSIT_PERCENT` | Acompte en % (50 par défaut) |

## À valider avant la mise en ligne

1. **Les prix sont indicatifs** : ce sont ceux de l'ancienne version, à confirmer par Siya Business (modifiables dans `/admin/produits`).
   Hypothèses prises : bâche 12 000 FCFA **par m²**, vinyle 10 000 FCFA **par m²**, cartes de visite 5 000 FCFA **par pack de 100**,
   flocage nom et numéro 2 500 FCFA **par maillot**, maillot 8 500 FCFA.
2. **Les textes de `/informations`** (acompte, annulation, droits sur les visuels) décrivent le fonctionnement réel du site, mais doivent être relus et validés.
3. **Le logo** fourni est une petite image (92 × 96 px) : à remplacer par une version haute définition dans `public/images/` pour un rendu net.
4. **Les produits « Mode & accessoires »** (baskets, sac, jean, chemise) viennent de l'ancien catalogue. À garder ou à masquer depuis l'administration.
5. **Adresse, horaires, réseaux sociaux, numéro de paiement** : à renseigner dans `.env` ; tant qu'ils sont vides, rien ne s'affiche.
6. **Photos** : les images actuelles sont des liens externes de l'ancien projet ; si un lien tombe, le site affiche un repère à la marque. Envoyez vos vraies photos depuis l'administration.

## Tests

```bash
php artisan test
```

Les 46 tests couvrent : le calcul de prix de chaque mode, les erreurs de personnalisation, l'idempotence de la commande, les fichiers refusés, le jeton de suivi,
chaque règle du parcours de commande (devis, maquette, production, paiement, annulation), la protection de l'administration et la gestion du catalogue.
Ils s'exécutent aussi à chaque `push` (GitHub Actions).

## Changements par rapport à la version précédente

- Le panier n'est plus indexé par produit : chaque ligne a sa personnalisation.
- Nouveaux : configurateur, devis, validation de maquette, fichiers, suivi par lien sécurisé, paiements enregistrés, administration, tests.
- **Schéma de base refait** : lancer `php artisan migrate:fresh --seed` (cela efface les données de démonstration locales).
- Fichiers de l'ancienne version supprimés : `StoreController`, `CartController`, `CheckoutController`, les pages `Cart/`, `Checkout/` et `Store/Index.vue`,
  l'ancienne migration `…_create_orders_table.php`.
- La palette de `tailwind.config.js` (bleu marine) est remplacée par celle du logo.

## Évolutions possibles

Paiement en ligne Wave / Orange Money (le principe existe dans le projet Fondé 44) · comptes clients avec historique · notifications SMS ·
galerie de réalisations · gestion des stocks de maillots · options avancées éditables depuis l'administration.
