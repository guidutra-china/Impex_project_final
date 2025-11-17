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
        // Update supplier_contacts table
        Schema::table('supplier_contacts', function (Blueprint $table) {
            // Change function column to enum
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
            ])->nullable()->change();
        });

        // Update client_contacts table
        Schema::table('client_contacts', function (Blueprint $table) {
            // Change function column to enum
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
            ])->nullable()->change();
        });

        // Update existing data that doesn't match enum values to 'Others'
        DB::table('supplier_contacts')
            ->whereNotIn('function', [
                'CEO', 'CTO', 'CFO', 'Manager', 'Supervisor', 'Analyst',
                'Specialist', 'Coordinator', 'Director', 'Consultant',
                'Sales', 'Sales Manager', 'Others'
            ])
            ->whereNotNull('function')
            ->update(['function' => 'Others']);

        DB::table('client_contacts')
            ->whereNotIn('function', [
                'CEO', 'CTO', 'CFO', 'Manager', 'Supervisor', 'Analyst',
                'Specialist', 'Coordinator', 'Director', 'Consultant',
                'Sales', 'Sales Manager', 'Others'
            ])
            ->whereNotNull('function')
            ->update(['function' => 'Others']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert supplier_contacts table
        Schema::table('supplier_contacts', function (Blueprint $table) {
            $table->string('function')->nullable()->change();
        });

        // Revert client_contacts table
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->string('function')->nullable()->change();
        });
    }
};