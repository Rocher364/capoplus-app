<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * VULN-13 / Phase 6 : Immutabilité stricte des journaux d'audit au niveau base de données.
     * Interdiction absolue des opérations UPDATE et DELETE sur la table audit_logs.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_update
                BEFORE UPDATE ON audit_logs
                BEGIN
                    SELECT RAISE(ABORT, 'Audit logs are immutable: UPDATE operation is forbidden.');
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_delete
                BEFORE DELETE ON audit_logs
                BEGIN
                    SELECT RAISE(ABORT, 'Audit logs are immutable: DELETE operation is forbidden.');
                END;
            ");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::unprepared("
                CREATE TRIGGER trg_audit_logs_prevent_update
                BEFORE UPDATE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit logs are immutable: UPDATE operation is forbidden.';
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER trg_audit_logs_prevent_delete
                BEFORE DELETE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit logs are immutable: DELETE operation is forbidden.';
                END;
            ");
        } elseif ($driver === 'pgsql') {
            DB::unprepared("
                CREATE OR REPLACE FUNCTION trg_audit_logs_immutable()
                RETURNS TRIGGER AS $$
                BEGIN
                    RAISE EXCEPTION 'Audit logs are immutable: mutation forbidden.';
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER trg_audit_logs_prevent_update
                BEFORE UPDATE ON audit_logs
                FOR EACH ROW
                EXECUTE FUNCTION trg_audit_logs_immutable();

                CREATE TRIGGER trg_audit_logs_prevent_delete
                BEFORE DELETE ON audit_logs
                FOR EACH ROW
                EXECUTE FUNCTION trg_audit_logs_immutable();
            ");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("DROP TRIGGER IF EXISTS trg_audit_logs_prevent_update;");
            DB::unprepared("DROP TRIGGER IF EXISTS trg_audit_logs_prevent_delete;");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::unprepared("DROP TRIGGER IF EXISTS trg_audit_logs_prevent_update;");
            DB::unprepared("DROP TRIGGER IF EXISTS trg_audit_logs_prevent_delete;");
        } elseif ($driver === 'pgsql') {
            DB::unprepared("DROP TRIGGER IF EXISTS trg_audit_logs_prevent_update ON audit_logs;");
            DB::unprepared("DROP TRIGGER IF EXISTS trg_audit_logs_prevent_delete ON audit_logs;");
            DB::unprepared("DROP FUNCTION IF EXISTS trg_audit_logs_immutable();");
        }
    }
};
