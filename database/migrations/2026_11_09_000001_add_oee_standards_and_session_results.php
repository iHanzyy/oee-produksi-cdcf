<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oee_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('machine_id')->constrained()->restrictOnDelete();
            $table->decimal('theoretical_output_kg', 12, 3);
            $table->decimal('ideal_cycle_time_hours_per_kg', 16, 10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'machine_id']);
        });

        Schema::table('production_sessions', function (Blueprint $table) {
            $table->decimal('actual_output_kg', 12, 3)->nullable();
            $table->decimal('theoretical_output_kg', 12, 3)->nullable();
            $table->decimal('ideal_cycle_time_hours_per_kg', 16, 10)->nullable();
            $table->unsignedInteger('operating_time_seconds')->nullable();
            $table->decimal('availability_percent', 14, 4)->nullable();
            $table->decimal('quality_percent', 14, 4)->nullable();
            $table->decimal('performance_percent', 14, 4)->nullable();
            $table->decimal('oee_percent', 14, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'actual_output_kg',
                'theoretical_output_kg',
                'ideal_cycle_time_hours_per_kg',
                'operating_time_seconds',
                'availability_percent',
                'quality_percent',
                'performance_percent',
                'oee_percent',
            ]);
        });

        Schema::dropIfExists('oee_standards');
    }
};
