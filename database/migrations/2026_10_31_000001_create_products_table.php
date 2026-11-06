<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('family')->index();                // maillots, textile, impression, creation, mode
            $table->string('pricing_mode', 10)->default('unit'); // unit | area | pack | quote
            $table->string('unit_label', 30)->default('pièce');  // pièce, m², pack de 100…
            $table->unsignedInteger('price')->nullable();        // FCFA ; null = sur devis. area : prix du m²
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->string('image_path')->nullable();            // photo envoyée depuis l'administration
            $table->unsignedSmallInteger('min_quantity')->default(1);
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('options')->nullable();                 // tailles, extras, choix, liste de joueurs…
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
