<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_see_all')
                ->default(false)
                ->after('guard_name')
                ->comment('Role can see all clients regardless of ownership');
        });

        // Set super_admin to can_see_all by default
        \DB::table('roles')
            ->where('name', 'super_admin')
            ->update(['can_see_all' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('can_see_all');
        });
    }
};
