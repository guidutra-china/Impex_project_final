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
        Schema::table('documents', function (Blueprint $table) {
            // Add polymorphic columns for Laravel standard naming
            $table->string('documentable_type', 100)->nullable()->after('document_type')
                ->comment('Polymorphic type: App\Models\Supplier, App\Models\Product, etc.');
            $table->unsignedBigInteger('documentable_id')->nullable()->after('documentable_type')
                ->comment('Polymorphic ID');
            
            // Add composite index for polymorphic relationship
            $table->index(['documentable_type', 'documentable_id'], 'idx_documentable_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documentable_composite');
            $table->dropColumn(['documentable_type', 'documentable_id']);
        });
    }
};
