<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();                       // SY-261102-AB12C
            $table->string('client_reference', 100)->nullable()->unique(); // idempotence (double clic)
            $table->string('customer_name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('fulfillment', 10)->default('pickup');        // pickup | delivery
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('notes')->nullable();                           // message du client
            $table->string('payment_preference', 20)->nullable();        // cash | wave | orange_money
            $table->string('status', 20)->default('received')->index();
            $table->unsignedInteger('estimated_total')->default(0);      // somme des lignes déjà chiffrées
            $table->boolean('has_pending_price')->default(false);        // au moins une ligne sur devis
            $table->unsignedInteger('confirmed_total')->nullable();      // prix fixé par l'atelier
            $table->text('quote_message')->nullable();                   // message joint au devis
            $table->unsignedTinyInteger('deposit_percent')->default(50);
            $table->text('internal_notes')->nullable();                  // jamais visible du client
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('family', 30)->nullable();
            $table->string('unit_label', 30)->default('pièce');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price')->nullable();          // null = sur devis
            $table->unsignedInteger('line_total')->nullable();
            $table->json('configuration')->nullable();                   // taille, extras, dimensions, liste de joueurs…
            $table->timestamps();
        });

        Schema::create('order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);                                  // artwork (client) | proof (maquette)
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('status', 20)->nullable();                    // maquettes : pending | approved | changes_requested
            $table->text('client_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('method', 20);
            $table->string('note')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('actor', 60);                                 // client, atelier, système
            $table->string('message');
            $table->boolean('visible_to_client')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_files');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
