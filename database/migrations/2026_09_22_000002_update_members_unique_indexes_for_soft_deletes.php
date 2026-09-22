<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['sqlite', 'pgsql'])) {
            // Remplacer les index uniques inconditionnels par des index partiels ignorant les soft-deleted
            DB::statement('DROP INDEX IF EXISTS members_telephone_unique;');
            DB::statement('CREATE UNIQUE INDEX members_telephone_unique ON members(telephone) WHERE deleted_at IS NULL;');

            DB::statement('DROP INDEX IF EXISTS members_nif_cin_unique;');
            DB::statement('CREATE UNIQUE INDEX members_nif_cin_unique ON members(nif_cin) WHERE deleted_at IS NULL;');

            DB::statement('DROP INDEX IF EXISTS members_email_unique;');
            DB::statement('CREATE UNIQUE INDEX members_email_unique ON members(email) WHERE deleted_at IS NULL;');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['sqlite', 'pgsql'])) {
            DB::statement('DROP INDEX IF EXISTS members_telephone_unique;');
            DB::statement('CREATE UNIQUE INDEX members_telephone_unique ON members(telephone);');

            DB::statement('DROP INDEX IF EXISTS members_nif_cin_unique;');
            DB::statement('CREATE UNIQUE INDEX members_nif_cin_unique ON members(nif_cin);');

            DB::statement('DROP INDEX IF EXISTS members_email_unique;');
            DB::statement('CREATE UNIQUE INDEX members_email_unique ON members(email);');
        }
    }
};
