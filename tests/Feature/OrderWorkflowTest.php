<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use App\Support\TrackingTokens;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Le parcours d'une commande côté atelier ET côté client : chaque règle de passage a son test. */
class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductSeeder::class);
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Siya', 'email' => 'admin@siya.test', 'password' => 'un-mot-de-passe-solide']);
    }

    private function order(array $attributes = []): Order
    {
        $order = Order::create($attributes + [
            'reference' => 'SY-261102-'.Str::upper(Str::random(5)),
            'customer_name' => 'Awa Diop', 'phone' => '+221771234567', 'fulfillment' => 'pickup',
            'status' => 'received', 'estimated_total' => 10000, 'deposit_percent' => 50,
        ]);
        $order->items()->create(['product_name' => 'T-shirt personnalisé', 'unit_label' => 'pièce', 'quantity' => 2, 'unit_price' => 5000, 'line_total' => 10000]);

        return $order;
    }

    private function asAdmin(): self
    {
        return $this->actingAs($this->admin);
    }

    private function setStatus(Order $order, string $to, array $extra = [])
    {
        return $this->asAdmin()->patch("/admin/commandes/{$order->reference}/statut", ['status' => $to] + $extra);
    }

    private function token(Order $order): string
    {
        return TrackingTokens::forOrder($order->reference);
    }

    public function test_the_admin_area_requires_a_login(): void
    {
        $order = $this->order();

        $this->get('/admin')->assertRedirect('/admin/connexion');
        $this->get("/admin/commandes/{$order->reference}")->assertRedirect('/admin/connexion');
        $this->patch("/admin/commandes/{$order->reference}/statut", ['status' => 'confirmed'])->assertRedirect('/admin/connexion');
        $this->assertSame('received', $order->refresh()->status->value);
    }

    public function test_a_fixed_price_order_can_go_straight_to_production(): void
    {
        $order = $this->order();

        $this->setStatus($order, 'confirmed')->assertSessionHas('success');
        $this->setStatus($order, 'production')->assertSessionHas('success');
        $this->setStatus($order, 'ready')->assertSessionHas('success');
        $this->setStatus($order, 'completed')->assertSessionHas('success');

        $this->assertSame('completed', $order->refresh()->status->value);
        $this->assertSame(4, $order->events()->count());
    }

    public function test_steps_cannot_be_skipped_or_reversed(): void
    {
        $order = $this->order();

        $this->setStatus($order, 'production')->assertSessionHas('error');   // reçue → production : interdit
        $this->setStatus($order, 'confirmed');
        $this->setStatus($order, 'received')->assertSessionHas('error');      // retour en arrière : interdit
        $this->assertSame('confirmed', $order->refresh()->status->value);

        $order->update(['status' => 'completed']);
        $this->setStatus($order, 'cancelled')->assertSessionHas('error');     // une commande terminée est figée
    }

    public function test_an_order_with_a_quote_only_line_needs_a_price_before_confirmation(): void
    {
        $order = $this->order(['has_pending_price' => true]);

        $this->setStatus($order, 'confirmed')->assertSessionHas('error');
        $this->setStatus($order, 'quoted')->assertSessionHas('error');        // « quoted » ne se choisit pas : on envoie un devis

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/devis", ['confirmed_total' => 25000, 'quote_message' => 'Livraison incluse'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('quoted', $order->status->value);
        $this->assertSame(25000, $order->total());
        $this->assertSame(12500, $order->depositDue());                    // acompte de 50 %

        $this->post("/suivi/{$order->reference}/devis", ['token' => $this->token($order)])->assertSessionHas('success');
        $this->assertSame('confirmed', $order->refresh()->status->value);
    }

    public function test_a_quote_must_be_a_positive_amount(): void
    {
        $order = $this->order();

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/devis", ['confirmed_total' => 0])->assertSessionHasErrors('confirmed_total');
        $this->asAdmin()->post("/admin/commandes/{$order->reference}/devis", [])->assertSessionHasErrors('confirmed_total');
    }

    public function test_the_client_cannot_accept_a_quote_that_was_not_sent(): void
    {
        $order = $this->order();

        $this->post("/suivi/{$order->reference}/devis", ['token' => $this->token($order)])->assertSessionHas('error');
        $this->post("/suivi/{$order->reference}/devis", ['token' => 'faux'])->assertNotFound();
    }

    public function test_proof_cycle_blocks_production_until_the_client_approves(): void
    {
        $order = $this->order(['status' => 'confirmed']);

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/maquettes", [
            'proof' => [UploadedFile::fake()->create('maquette.pdf', 120, 'application/pdf')],
        ])->assertSessionHas('success');

        $this->assertSame('proofing', $order->refresh()->status->value);
        $proof = $order->files()->where('kind', OrderFile::PROOF)->firstOrFail();
        $this->assertSame(OrderFile::PENDING, $proof->status);

        $this->setStatus($order, 'production')->assertSessionHas('error');    // maquette non validée

        // Le client demande une modification : le commentaire est obligatoire.
        $url = "/suivi/{$order->reference}/maquettes/{$proof->id}";
        $this->post($url, ['token' => $this->token($order), 'decision' => 'changes'])->assertSessionHas('error');
        $this->post($url, ['token' => $this->token($order), 'decision' => 'changes', 'comment' => 'Le numéro est trop petit'])->assertSessionHas('success');
        $this->assertSame(OrderFile::CHANGES, $proof->refresh()->status);
        $this->setStatus($order, 'production')->assertSessionHas('error');

        $this->post($url, ['token' => $this->token($order), 'decision' => 'approve'])->assertSessionHas('success');
        $this->assertSame(OrderFile::APPROVED, $proof->refresh()->status);

        $this->setStatus($order, 'production')->assertSessionHas('success');
    }

    public function test_the_workshop_can_launch_production_on_an_oral_agreement(): void
    {
        $order = $this->order(['status' => 'confirmed']);
        $this->asAdmin()->post("/admin/commandes/{$order->reference}/maquettes", ['proof' => [UploadedFile::fake()->create('m.pdf', 50, 'application/pdf')]]);

        $this->setStatus($order, 'production', ['force' => true])->assertSessionHas('success');
        $this->assertSame('production', $order->refresh()->status->value);
    }

    public function test_proofs_can_only_be_sent_once_the_order_is_confirmed(): void
    {
        $order = $this->order(); // reçue

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/maquettes", ['proof' => [UploadedFile::fake()->create('m.pdf', 50, 'application/pdf')]])
            ->assertSessionHas('error');
        $this->assertSame(0, $order->files()->count());
    }

    public function test_the_client_downloads_only_files_of_their_own_order(): void
    {
        $order = $this->order(['status' => 'confirmed']);
        $this->asAdmin()->post("/admin/commandes/{$order->reference}/maquettes", ['proof' => [UploadedFile::fake()->create('m.pdf', 50, 'application/pdf')]]);
        $file = $order->files()->firstOrFail();

        $own = $this->get("/suivi/{$order->reference}/fichiers/{$file->id}?token={$this->token($order)}");
        $own->assertOk();
        $this->assertSame('nosniff', $own->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('attachment', $own->headers->get('Content-Disposition'));

        $other = $this->order();
        $this->get("/suivi/{$other->reference}/fichiers/{$file->id}?token={$this->token($other)}")->assertNotFound();
        $this->get("/suivi/{$order->reference}/fichiers/{$file->id}")->assertNotFound();

        // Un visiteur (non connecté) n'accède jamais aux fichiers via l'administration.
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get("/admin/commandes/{$order->reference}/fichiers/{$file->id}")->assertRedirect('/admin/connexion');
    }

    public function test_payments_are_recorded_against_the_balance(): void
    {
        $order = $this->order(['status' => 'confirmed']);

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/paiements", ['amount' => 5000, 'method' => 'wave', 'note' => 'Acompte'])
            ->assertSessionHas('success');
        $order->refresh()->load('payments');
        $this->assertSame(5000, $order->paid());
        $this->assertSame(5000, $order->balance());

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/paiements", ['amount' => 9000, 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->asAdmin()->post("/admin/commandes/{$order->reference}/paiements", ['amount' => 5000, 'method' => 'cash'])->assertSessionHas('success');
        $this->assertSame(0, $order->refresh()->load('payments')->balance());
    }

    public function test_a_cancelled_order_takes_no_payment(): void
    {
        $order = $this->order(['status' => 'cancelled']);

        $this->asAdmin()->post("/admin/commandes/{$order->reference}/paiements", ['amount' => 1000, 'method' => 'cash'])->assertSessionHas('error');
        $this->assertSame(0, $order->payments()->count());
    }
}
