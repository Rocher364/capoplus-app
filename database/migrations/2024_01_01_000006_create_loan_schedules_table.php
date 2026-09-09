<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('numero_echeance');
            $table->date('date_echeance');

            $table->decimal('capital', 15, 2);
            $table->decimal('interet', 15, 2);
            $table->decimal('montant_total', 15, 2);

            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('solde_restant', 15, 2);

            $table->enum('statut', ['a_venir', 'due', 'en_retard', 'partiellement_payee', 'payee'])
                ->default('a_venir');

            $table->timestamps();

            $table->unique(['loan_id', 'numero_echeance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
