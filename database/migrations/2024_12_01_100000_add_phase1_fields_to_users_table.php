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
        Schema::table('users', function (Blueprint $table) {
            // Avatar/Photo
            $table->string('avatar')->nullable()->after('email');
            
            // Phone number
            $table->string('phone', 20)->nullable()->after('avatar');
            
            // User status (active, inactive, suspended)
            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('active')
                  ->after('email_verified_at');
            
            // Last login tracking
            $table->timestamp('last_login_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar',
                'phone',
                'status',
                'last_login_at',
            ]);
        });
    }
};
