<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // FULLTEXT index is only supported by MySQL/MariaDB
        // For SQLite (used in tests), we use a regular index instead
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Add FULLTEXT index for fast searching on name column (MySQL only)
            DB::statement('ALTER TABLE suppliers ADD FULLTEXT INDEX ft_suppliers_search (name)');
        } else {
            // For other databases (SQLite, PostgreSQL), use regular index
            Schema::table('suppliers', function (Blueprint $table) {
                $table->index('name', 'ft_suppliers_search');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE suppliers DROP INDEX ft_suppliers_search');
        } else {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropIndex('ft_suppliers_search');
            });
        }
    }
};
