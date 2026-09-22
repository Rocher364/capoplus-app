<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Contrainte d'unicité accounts.member_id (VULN-08)
        Schema::table('accounts', function (Blueprint $table) {
            $table->unique('member_id');
        });

        // 2. Contrainte d'intégrité : solde >= 0
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE accounts ADD CONSTRAINT check_account_solde_positive CHECK (solde >= 0);');
        } elseif ($driver === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS check_account_solde_positive_insert
                BEFORE INSERT ON accounts
                BEGIN
                    SELECT CASE WHEN NEW.solde < 0 THEN RAISE(ABORT, 'Le solde ne peut pas être négatif') END;
                END;
            ");
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS check_account_solde_positive_update
                BEFORE UPDATE OF solde ON accounts
                BEGIN
                    SELECT CASE WHEN NEW.solde < 0 THEN RAISE(ABORT, 'Le solde ne peut pas être négatif') END;
                END;
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE accounts DROP CONSTRAINT IF EXISTS check_account_solde_positive;');
        } elseif ($driver === 'sqlite') {
            DB::unprepared("DROP TRIGGER IF EXISTS check_account_solde_positive_insert;");
            DB::unprepared("DROP TRIGGER IF EXISTS check_account_solde_positive_update;");
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['member_id']);
        });
    }
};
