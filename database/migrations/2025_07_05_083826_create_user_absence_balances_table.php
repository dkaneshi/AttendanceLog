<?php

declare(strict_types=1);

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
        Schema::create('user_absence_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->decimal('vacation_hours_total', 6, 2)->default(160.00); // 20 days × 8 hours
            $table->decimal('vacation_hours_used', 6, 2)->default(0.00);
            $table->decimal('sick_hours_total', 6, 2)->default(80.00); // 10 days × 8 hours
            $table->decimal('sick_hours_used', 6, 2)->default(0.00);
            $table->integer('year');
            $table->softDeletes();
            $table->timestamps();

            // Unique constraint to ensure one record per user per year
            $table->unique(['user_id', 'year']);
            
            // Add indexes for performance
            $table->index(['user_id', 'year']);
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_absence_balances');
    }
};
