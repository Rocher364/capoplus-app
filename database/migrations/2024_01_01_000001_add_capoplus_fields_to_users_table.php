<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'agent', 'auditeur'])->default('agent')->after('password');
            $table->string('telephone')->nullable()->after('role');
            $table->enum('statut', ['actif', 'inactif', 'verrouille'])->default('actif')->after('telephone');

            $table->unsignedTinyInteger('tentatives_echouees')->default(0)->after('statut');
            $table->timestamp('verrouille_jusqu_a')->nullable()->after('tentatives_echouees');
            $table->timestamp('derniere_connexion_a')->nullable()->after('verrouille_jusqu_a');
            $table->string('derniere_connexion_ip')->nullable()->after('derniere_connexion_a');

            $table->text('two_factor_secret')->nullable()->after('derniere_connexion_ip');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'telephone', 'statut', 'tentatives_echouees',
                'verrouille_jusqu_a', 'derniere_connexion_a', 'derniere_connexion_ip',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            ]);
        });
    }
};
