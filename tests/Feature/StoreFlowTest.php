<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFile;
use App\Support\TrackingTokens;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
        Storage::fake('local');
    }

    private function customer(array $overrides = []): array
    {
        return array_replace([
            'client_reference' => 'SYCLIENT-test-0001',
            'customer_name' => 'Awa Diop',
            'phone' => '77 123 45 67',
            'email' => '',
            'fulfillment' => 'pickup',
            'address' => '',
            'city' => '',
            'notes' => '',
            'payment_preference' => 'wave',
            'accept_terms' => true,
        ], $overrides);
    }

    private function addTshirt(int $qty = 2, string $size = 'M'): void
    {
        $this->post('/panier', ['product' => 't-shirt-personnalise', 'quantity' => $qty, 'size' => $size])->assertRedirect('/panier');
    }

    public function test_catalogue_pages_render(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Store/Home')->has('featured'));
        $this->get('/boutique?famille=maillots')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Store/Shop')->has('products', 4)->where('filters.famille', 'maillots'));
        $this->get('/boutique?q=bâche')->assertInertia(fn (Assert $p) => $p->has('products', 1));
        $this->get('/produit/maillot-flocage')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Store/Show')->where('product.options.roster.fields.0', 'name'));
        $this->get('/produit/inconnu')->assertNotFound();
    }

    public function test_the_same_product_can_be_added_twice_with_different_options(): void
    {
        $this->addTshirt(2, 'M');
        $this->addTshirt(1, 'XL');

        $this->get('/panier')->assertInertia(fn (Assert $p) => $p
            ->component('Store/Cart')
            ->has('cart.items', 2)
            ->where('cart.subtotal', 15000));
    }

    public function test_invalid_options_come_back_as_field_errors(): void
    {
        $this->post('/panier', ['product' => 't-shirt-personnalise', 'quantity' => 1])->assertSessionHasErrors('size');
        $this->post('/panier', ['product' => 'bache-publicitaire', 'quantity' => 1])->assertSessionHasErrors(['width_cm', 'height_cm']);
        $this->get('/panier')->assertInertia(fn (Assert $p) => $p->has('cart.items', 0));
    }

    public function test_cart_quantity_can_be_changed_and_lines_removed(): void
    {
        $this->addTshirt(2);
        $id = $this->app['session.store']->get('cart') ? array_key_first($this->app['session.store']->get('cart')) : null;

        $this->patch("/panier/{$id}", ['quantity' => 4])->assertRedirect();
        $this->get('/panier')->assertInertia(fn (Assert $p) => $p->where('cart.subtotal', 20000));

        $this->delete("/panier/{$id}")->assertRedirect();
        $this->get('/panier')->assertInertia(fn (Assert $p) => $p->has('cart.items', 0));
    }

    public function test_checkout_redirects_when_the_cart_is_empty(): void
    {
        $this->get('/commande')->assertRedirect('/panier');
    }

    public function test_a_pickup_order_is_created_with_prices_read_from_the_catalogue(): void
    {
        $this->addTshirt(2);

        $response = $this->post('/commande', $this->customer());

        $order = Order::with('items')->firstOrFail();
        $response->assertRedirect(route('tracking.show', ['reference' => $order->reference, 'token' => TrackingTokens::forOrder($order->reference)]));

        $this->assertMatchesRegularExpression(TrackingTokens::REFERENCE, $order->reference);
        $this->assertSame('received', $order->status->value);
        $this->assertSame(10000, $order->estimated_total);
        $this->assertFalse($order->has_pending_price);
        $this->assertSame('+221771234567', $order->phone);
        $this->assertSame('T-shirt personnalisé', $order->items[0]->product_name);
        $this->assertSame('M', $order->items[0]->configuration['size']);
        $this->assertDatabaseCount('order_events', 1);

        $this->get('/panier')->assertInertia(fn (Assert $p) => $p->has('cart.items', 0)); // panier vidé
    }

    public function test_delivery_needs_an_address(): void
    {
        $this->addTshirt();

        $this->post('/commande', $this->customer(['fulfillment' => 'delivery']))->assertSessionHasErrors(['address', 'city']);
        $this->assertDatabaseCount('orders', 0);

        $this->post('/commande', $this->customer(['fulfillment' => 'delivery', 'address' => 'Ouakam, rue 12', 'city' => 'Dakar']))->assertSessionHasNoErrors();
        $this->assertSame('Ouakam, rue 12', Order::firstOrFail()->address);
    }

    public function test_replaying_the_same_reference_never_creates_a_second_order(): void
    {
        $this->addTshirt();
        $this->post('/commande', $this->customer());
        $this->addTshirt();
        $this->post('/commande', $this->customer());

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_quote_only_lines_are_flagged_and_left_out_of_the_estimate(): void
    {
        $this->addTshirt(1);
        $this->post('/panier', ['product' => 'infographie', 'quantity' => 1, 'notes' => 'Affiche de conférence']);
        $this->post('/commande', $this->customer());

        $order = Order::firstOrFail();
        $this->assertTrue($order->has_pending_price);
        $this->assertSame(5000, $order->estimated_total);
        $this->assertNull($order->items()->where('product_name', 'Infographie')->first()->line_total);
    }

    public function test_customer_files_are_stored_privately_and_checked(): void
    {
        $this->addTshirt();
        $this->post('/commande', $this->customer([
            'files' => [UploadedFile::fake()->create('logo.png', 200, 'image/png'), UploadedFile::fake()->create('visuel.pdf', 200, 'application/pdf')],
        ]))->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(2, $order->files()->where('kind', OrderFile::ARTWORK)->count());
        foreach ($order->files as $file) {
            Storage::disk('local')->assertExists($file->path);
        }
    }

    public function test_dangerous_file_types_are_refused(): void
    {
        $this->addTshirt();

        $this->post('/commande', $this->customer(['files' => [UploadedFile::fake()->create('virus.php', 10)]]))->assertSessionHasErrors('files.0');
        $this->post('/commande', $this->customer(['files' => [UploadedFile::fake()->create('trop-lourd.pdf', 20000, 'application/pdf')]]))->assertSessionHasErrors('files.0');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->addTshirt();
        $this->post('/commande', $this->customer(['accept_terms' => false]))->assertSessionHasErrors('accept_terms');
    }

    public function test_tracking_needs_a_valid_token(): void
    {
        $this->addTshirt();
        $this->post('/commande', $this->customer());
        $order = Order::firstOrFail();

        $this->get("/suivi/{$order->reference}")->assertNotFound();
        $this->get("/suivi/{$order->reference}?token=".str_repeat('a', 43))->assertNotFound();

        $this->get("/suivi/{$order->reference}?token=".TrackingTokens::forOrder($order->reference))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p
                ->component('Store/Track')
                ->where('order.reference', $order->reference)
                ->where('order.status', 'received')
                ->where('order.can.cancel', true)
                ->missing('order.internalNotes'));
    }

    public function test_the_client_can_cancel_before_confirmation_only(): void
    {
        $this->addTshirt();
        $this->post('/commande', $this->customer());
        $order = Order::firstOrFail();
        $token = TrackingTokens::forOrder($order->reference);

        $order->update(['status' => 'production']);
        $this->post("/suivi/{$order->reference}/annuler", ['token' => $token])->assertSessionHas('error');
        $this->assertSame('production', $order->refresh()->status->value);

        $order->update(['status' => 'received']);
        $this->post("/suivi/{$order->reference}/annuler", ['token' => $token])->assertSessionHas('success');
        $this->assertSame('cancelled', $order->refresh()->status->value);
    }

    public function test_quote_and_contact_forms_store_an_inquiry(): void
    {
        $this->post('/devis', ['name' => 'Moussa', 'phone' => '78 111 22 33', 'service' => 'Infographie', 'message' => 'Je voudrais 5 infographies pour une conférence.'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('inquiries', ['type' => 'quote', 'phone' => '+221781112233', 'status' => 'new']);

        $this->post('/contact', ['name' => 'M', 'phone' => '12', 'message' => 'court'])->assertSessionHasErrors(['name', 'phone', 'message']);
        $this->assertDatabaseCount('inquiries', 1);
    }
}
