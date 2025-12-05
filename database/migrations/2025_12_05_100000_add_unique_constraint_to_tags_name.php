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
        // First, remove any duplicate tags keeping the oldest one
        $duplicates = DB::table('tags')
            ->select('name', DB::raw('MIN(id) as keep_id'))
            ->groupBy('name')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Delete all tags with this name except the one we want to keep
            DB::table('tags')
                ->where('name', $duplicate->name)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        // Now add the unique constraint
        Schema::table('tags', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
