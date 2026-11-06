<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductConfigurator;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Le calcul de prix est le cœur du site : chaque mode de prix a son test, sur le vrai catalogue de départ. */
class ProductConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    private ProductConfigurator $configurator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
        $this->configurator = new ProductConfigurator;
    }

    private function product(string $slug): Product
    {
        return Product::where('slug', $slug)->firstOrFail();
    }

    private function errorsOf(string $slug, array $input): array
    {
        try {
            $this->configurator->resolve($this->product($slug), $input);
        } catch (ValidationException $e) {
            return $e->errors();
        }

        return [];
    }

    public function test_unit_price_is_price_times_quantity(): void
    {
        $r = $this->configurator->resolve($this->product('t-shirt-personnalise'), ['quantity' => 3, 'size' => 'L']);

        $this->assertSame(5000, $r['unit_price']);
        $this->assertSame(15000, $r['line_total']);
        $this->assertFalse($r['pending']);
        $this->assertContains('Taille : L', $r['summary']);
    }

    public function test_a_size_outside_the_list_is_refused(): void
    {
        $this->assertArrayHasKey('size', $this->errorsOf('t-shirt-personnalise', ['quantity' => 1, 'size' => 'XXXL']));
        $this->assertArrayHasKey('size', $this->errorsOf('t-shirt-personnalise', ['quantity' => 1]));
    }

    public function test_quantity_is_bounded(): void
    {
        $this->assertArrayHasKey('quantity', $this->errorsOf('t-shirt-personnalise', ['quantity' => 0, 'size' => 'M']));
        $this->assertArrayHasKey('quantity', $this->errorsOf('t-shirt-personnalise', ['quantity' => 5000, 'size' => 'M']));
    }

    public function test_team_jerseys_with_flocking_cost_jersey_plus_flocking_per_player(): void
    {
        $r = $this->configurator->resolve($this->product('maillot-flocage'), ['roster' => [
            ['name' => 'diop', 'number' => '10', 'size' => 'L'],
            ['name' => 'Ndiaye', 'number' => '7', 'size' => 'M'],
            ['name' => '', 'number' => '1', 'size' => 'XL'],
        ]]);

        $this->assertSame(3, $r['quantity']);              // la quantité vient de la liste
        $this->assertSame(11000, $r['unit_price']);       // 8 500 (maillot) + 2 500 (flocage)
        $this->assertSame(33000, $r['line_total']);
        $this->assertSame('DIOP', $r['config']['roster'][0]['name']); // nom mis en majuscules
        $this->assertContains('Flocage nom & numéro', $r['summary']);
    }

    public function test_roster_lines_report_their_own_errors(): void
    {
        $errors = $this->errorsOf('maillot-flocage', ['roster' => [
            ['name' => 'A', 'number' => '10', 'size' => 'L'],
            ['name' => 'B', 'number' => '', 'size' => 'L'],      // numéro manquant
            ['name' => 'C', 'number' => 'ab', 'size' => 'M'],    // numéro invalide
            ['name' => 'D', 'number' => '9', 'size' => 'XS'],    // taille inconnue
        ]]);

        $this->assertArrayHasKey('roster.1.number', $errors);
        $this->assertArrayHasKey('roster.2.number', $errors);
        $this->assertArrayHasKey('roster.3.size', $errors);
        $this->assertArrayNotHasKey('roster.0.number', $errors);
        $this->assertArrayHasKey('roster', $this->errorsOf('maillot-flocage', ['roster' => []]));
    }

    public function test_banner_price_follows_the_area(): void
    {
        $r = $this->configurator->resolve($this->product('bache-publicitaire'), ['quantity' => 2, 'width_cm' => 200, 'height_cm' => 100]);

        // 200 × 100 cm = 2 m² ; 12 000 FCFA/m² → 24 000 par bâche ; × 2
        $this->assertSame(24000, $r['unit_price']);
        $this->assertSame(48000, $r['line_total']);
        $this->assertStringContainsString('200 × 100 cm', $r['summary'][0]);
    }

    public function test_banner_dimensions_must_be_in_range(): void
    {
        $errors = $this->errorsOf('bache-publicitaire', ['quantity' => 1, 'width_cm' => 5, 'height_cm' => 5000]);

        $this->assertArrayHasKey('width_cm', $errors);
        $this->assertArrayHasKey('height_cm', $errors);
    }

    public function test_business_cards_are_sold_in_packs(): void
    {
        $r = $this->configurator->resolve($this->product('cartes-de-visite'), ['quantity' => 3, 'choices' => ['faces' => 'recto_verso']]);

        $this->assertSame(15000, $r['line_total']);
        $this->assertContains('3 packs de 100 = 300 exemplaires', $r['summary']);
        $this->assertContains('Impression : Recto-verso', $r['summary']);
    }

    public function test_a_choice_defaults_to_its_first_value_and_rejects_unknown_ones(): void
    {
        $r = $this->configurator->resolve($this->product('affiche-publicitaire'), ['quantity' => 1]);
        $this->assertSame('A3', $r['config']['choices']['format']);

        $this->assertArrayHasKey('choices.format', $this->errorsOf('affiche-publicitaire', ['quantity' => 1, 'choices' => ['format' => 'A0']]));
    }

    public function test_services_without_a_price_are_pending_quotes(): void
    {
        $r = $this->configurator->resolve($this->product('infographie'), ['quantity' => 1, 'notes' => '5 visuels pour une conférence']);

        $this->assertTrue($r['pending']);
        $this->assertNull($r['unit_price']);
        $this->assertNull($r['line_total']);
        $this->assertSame('5 visuels pour une conférence', $r['config']['notes']);
    }

    public function test_a_client_cannot_inject_a_price_or_an_unknown_option(): void
    {
        $r = $this->configurator->resolve($this->product('t-shirt-personnalise'), [
            'quantity' => 1, 'size' => 'M', 'price' => 1, 'unit_price' => 1, 'extras' => ['gratuit'],
        ]);

        $this->assertSame(5000, $r['line_total']);
        $this->assertArrayNotHasKey('extras', $r['config']);
    }

    public function test_notes_are_limited(): void
    {
        $this->assertArrayHasKey('notes', $this->errorsOf('t-shirt-personnalise', ['quantity' => 1, 'size' => 'M', 'notes' => str_repeat('a', 501)]));
    }
}
