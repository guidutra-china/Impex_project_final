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
        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->bigInteger('supplier_id');
            $table->string('email', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('wechat', 255)->nullable();
            // TODO: `function` enum('CEO','CTO','CFO','Manager','Supervisor','Analyst','Specialist','Coordinator','Director','Consultant','Sales','Sales Manager','Others') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');
    }
};
