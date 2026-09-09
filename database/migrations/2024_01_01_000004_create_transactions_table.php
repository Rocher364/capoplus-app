<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('reference')->unique();
            $table->enum('type', ['depot', 'retrait'])->index();
            $table->decimal('montant', 15, 2);
            $table->enum('moyen', ['especes', 'cheque', 'virement', 'autre'])->default('especes');

            $table->decimal('solde_apres', 15, 2);

            $table->timestamp('effectuee_le');
            $table->text('description')->nullable();

            $table->nullableMorphs('operation');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
