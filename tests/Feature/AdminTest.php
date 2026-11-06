<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
        $this->admin = User::create(['name' => 'Siya', 'email' => 'admin@siya.test', 'password' => 'un-mot-de-passe-solide']);
    }

    public function test_login_accepts_the_right_password_only(): void
    {
        $this->post('/admin/connexion', ['email' => 'admin@siya.test', 'password' => 'mauvais'])->assertSessionHasErrors('email');
        $this->post('/admin/connexion', ['email' => 'inconnu@siya.test', 'password' => 'mauvais'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/admin/connexion', ['email' => 'ADMIN@siya.test', 'password' => 'un-mot-de-passe-solide'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_a_deactivated_account_cannot_log_in_or_stay_in(): void
    {
        $this->admin->update(['is_active' => false]);

        $this->post('/admin/connexion', ['email' => 'admin@siya.test', 'password' => 'un-mot-de-passe-solide'])->assertSessionHasErrors('email');
        $this->actingAs($this->admin)->get('/admin')->assertRedirect('/admin/connexion');
    }

    public function test_dashboard_orders_and_inquiries_pages_render(): void
    {
        Order::create(['reference' => 'SY-261102-AAAAA', 'customer_name' => 'Awa', 'phone' => '+221771234567', 'fulfillment' => 'pickup', 'status' => 'received', 'estimated_total' => 5000, 'deposit_percent' => 50]);
        Inquiry::create(['reference' => 'DV-261102-AAAAA', 'type' => 'quote', 'name' => 'Moussa', 'phone' => '+221781112233', 'message' => 'Besoin de 5 infographies']);

        $this->actingAs($this->admin)->get('/admin')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Dashboard')->where('newInquiries', 1)->where('todo.0.count', 1));

        $this->actingAs($this->admin)->get('/admin/commandes?statut=received&q=Awa')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Orders/Index')->has('orders.data', 1));
        $this->actingAs($this->admin)->get('/admin/commandes?statut=production')->assertInertia(fn (Assert $p) => $p->has('orders.data', 0));

        $this->actingAs($this->admin)->get('/admin/commandes/SY-261102-AAAAA')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Orders/Show')->where('order.reference', 'SY-261102-AAAAA')->has('order.nextStatuses'));

        $this->actingAs($this->admin)->get('/admin/demandes')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Admin/Inquiries')->has('inquiries.data', 1));
        $this->actingAs($this->admin)->patch('/admin/demandes/'.Inquiry::first()->id, ['status' => 'contacted', 'staff_notes' => 'Rappelé'])->assertSessionHas('success');
        $this->assertSame('contacted', Inquiry::first()->status);
    }

    public function test_the_admin_can_change_a_price_and_hide_a_product(): void
    {
        $product = Product::where('slug', 't-shirt-personnalise')->firstOrFail();

        $this->actingAs($this->admin)->post("/admin/produits/{$product->slug}", [
            'name' => 'T-shirt premium', 'description' => 'Coton épais', 'unit_label' => 'pièce', 'price' => 6500, 'min_quantity' => 1,
            'sort_order' => 1, 'is_active' => false, 'is_featured' => true, 'sizes' => 'M, L, XL ,L',
        ])->assertRedirect('/admin/produits');

        $product->refresh();
        $this->assertSame(6500, $product->price);
        $this->assertSame(['M', 'L', 'XL'], $product->options['sizes']);       // nettoyées, sans doublon
        $this->assertTrue($product->options['needs_artwork']);                  // les autres options sont conservées

        $this->get('/produit/t-shirt-personnalise')->assertNotFound();          // masqué du site
        $this->get('/boutique')->assertInertia(fn (Assert $p) => $p->where('products', fn ($list) => collect($list)->doesntContain('slug', 't-shirt-personnalise')));
    }

    public function test_a_product_photo_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $product = Product::where('slug', 'sac-de-ville')->firstOrFail();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $payload = ['name' => $product->name, 'description' => $product->description, 'unit_label' => 'pièce', 'price' => 12000,
            'min_quantity' => 1, 'sort_order' => 3, 'is_active' => true, 'is_featured' => false];

        $this->actingAs($this->admin)->post("/admin/produits/{$product->slug}", $payload + ['image' => UploadedFile::fake()->createWithContent('photo.png', $png)])
            ->assertSessionHasNoErrors();
        $first = $product->refresh()->image_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->admin)->post("/admin/produits/{$product->slug}", $payload + ['image' => UploadedFile::fake()->createWithContent('autre.png', $png)]);
        Storage::disk('public')->assertMissing($first);                          // l'ancienne photo est supprimée

        $this->actingAs($this->admin)->post("/admin/produits/{$product->slug}", $payload + ['remove_image' => true]);
        $this->assertNull($product->refresh()->image_path);
    }

    public function test_a_non_image_cannot_be_uploaded_as_a_product_photo(): void
    {
        $product = Product::first();

        $this->actingAs($this->admin)->post("/admin/produits/{$product->slug}", [
            'name' => 'X', 'description' => 'Y', 'unit_label' => 'pièce', 'price' => 100, 'min_quantity' => 1, 'sort_order' => 1,
            'is_active' => true, 'is_featured' => false, 'image' => UploadedFile::fake()->create('script.php', 10),
        ])->assertSessionHasErrors('image');
    }

    public function test_the_create_admin_command_checks_the_password(): void
    {
        $this->artisan('siya:admin', ['email' => 'nouveau@siya.test'])
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'court')
            ->expectsOutput('Le mot de passe doit contenir au moins 12 caractères.')
            ->assertFailed();

        $this->artisan('siya:admin', ['email' => 'nouveau@siya.test', '--name' => 'Gérant'])
            ->expectsQuestion('Mot de passe (12 caractères minimum)', 'un-bon-mot-de-passe')
            ->expectsQuestion('Confirmez le mot de passe', 'un-bon-mot-de-passe')
            ->assertSuccessful();

        $this->assertSame('Gérant', User::where('email', 'nouveau@siya.test')->value('name'));
    }
}
