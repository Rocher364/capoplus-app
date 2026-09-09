<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('numero_membre')->unique();
            $table->string('prenom');
            $table->string('nom');
            $table->string('nif_cin')->nullable()->unique(); // Kolòn NIF/CIN ki te manke a
            $table->date('date_naissance')->nullable();
            $table->enum('sexe', ['M', 'F', 'Autre'])->nullable();

            $table->enum('type_piece_identite', ['CIN', 'Passeport', 'Permis', 'Autre'])->nullable();
            $table->string('numero_piece_identite')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('piece_identite_path')->nullable();

            $table->string('telephone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('adresse')->nullable();

            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');

            $table->foreignId('cree_par_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Endèks pou akselere rechèch ak filtre yo nan Controller la
            $table->index(['nom', 'prenom']);
            $table->index('telephone');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};