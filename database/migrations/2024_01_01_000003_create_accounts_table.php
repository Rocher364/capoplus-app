<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();

            $table->string('numero_compte')->unique();
            $table->decimal('solde', 15, 2)->default(0);

            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->text('raison_blocage')->nullable();
            $table->foreignId('bloque_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('bloque_le')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
