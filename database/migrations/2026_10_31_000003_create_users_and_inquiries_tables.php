<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Demandes de devis et messages de contact (infographie, design, projets sur mesure…).
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();                       // DV-261102-AB12C
            $table->string('type', 10);                                  // quote | contact
            $table->string('name', 120);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('service', 120)->nullable();
            $table->text('message');
            $table->string('status', 15)->default('new');                // new | contacted | closed
            $table->text('staff_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('users');
    }
};
