<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->enum('function', [
                'CEO',
                'CTO',
                'CFO',
                'Manager',
                'Supervisor',
                'Analyst',
                'Specialist',
                'Coordinator',
                'Director',
                'Consultant',
                'Sales',
                'Sales Manager',
                'Others'
            ])->nullable()->after('wechat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->dropColumn('function');
        });
    }
};
