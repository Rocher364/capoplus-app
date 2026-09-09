<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();

            $table->string('numero_pret')->unique();
            $table->decimal('montant_demande', 15, 2);
            $table->decimal('montant_approuve', 15, 2)->nullable();
            $table->text('motif');
            $table->unsignedSmallInteger('duree_mois');

            $table->enum('type_taux', ['fixe', 'degressif', 'palier'])->default('fixe');
            $table->decimal('taux_interet', 6, 3);
            $table->enum('methode_calcul', ['simple', 'degressif', 'constant'])->default('constant');

            $table->enum('statut', [
                'demande', 'approuve', 'rejete', 'decaisse', 'en_retard', 'solde', 'annule',
            ])->default('demande');

            $table->text('justification_decision')->nullable();
            $table->foreignId('demande_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approuve_par_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('date_demande');
            $table->timestamp('date_decision')->nullable();
            $table->timestamp('date_decaissement')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
