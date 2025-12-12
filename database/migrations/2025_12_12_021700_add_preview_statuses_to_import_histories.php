<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the status enum to include new preview statuses
        DB::statement("ALTER TABLE `import_histories` MODIFY COLUMN `status` ENUM('pending', 'analyzing', 'ready', 'generating_preview', 'preview_ready', 'importing', 'completed', 'failed') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original status values
        DB::statement("ALTER TABLE `import_histories` MODIFY COLUMN `status` ENUM('pending', 'analyzing', 'ready', 'importing', 'completed', 'failed') NOT NULL DEFAULT 'pending'");
    }
};
