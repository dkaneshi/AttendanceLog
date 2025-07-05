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
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->date('date');
            $table->time('shift_start_time')->nullable();
            $table->time('lunch_start_time')->nullable();
            $table->time('lunch_end_time')->nullable();
            $table->time('shift_end_time')->nullable();
            $table->decimal('vacation_hours', 4, 2)->default(0);
            $table->decimal('sick_hours', 4, 2)->default(0);
            $table->decimal('total_hours', 4, 2)->default(0);
            $table->decimal('overtime_hours', 4, 2)->default(0);
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'requires_correction'])->default('pending');
            $table->text('manager_comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->softDeletes();
            $table->timestamps();

            // Ensure one entry per user per date
            $table->unique(['user_id', 'date']);

            // Add indexes for performance
            $table->index(['date', 'user_id']);
            $table->index(['approval_status', 'manager_id']);
            $table->index('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
