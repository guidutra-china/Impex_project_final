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
        Schema::create('supplier_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('supplier_id');
            $table->date('review_date');
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->decimal('delivery_score', 10, 2);
            $table->decimal('quality_score', 10, 2);
            $table->decimal('pricing_score', 10, 2);
            $table->decimal('communication_score', 10, 2);
            $table->decimal('overall_score', 10, 2);
            // TODO: `rating` enum('A','B','C','D','F') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->text('strengths');
            $table->text('weaknesses');
            $table->text('recommendations');
            // TODO: `decision` enum('continue','monitor','improve_required','discontinue') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->bigInteger('reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_reviews');
    }
};
