<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oee_standards', function (Blueprint $table) {
            $table->string('process_type', 20)->nullable();
            $table->string('output_unit', 20)->nullable();
            $table->unsignedInteger('planned_time_minutes')->nullable();
            $table->decimal('theoretical_output', 15, 3)->nullable();
            $table->decimal('ideal_cycle_time_minutes_per_unit', 16, 10)->nullable();
        });

        Schema::table('production_sessions', function (Blueprint $table) {
            $table->string('process_type', 20)->nullable();
            $table->string('output_unit', 20)->nullable();
            $table->decimal('theoretical_output', 15, 3)->nullable();
            $table->decimal('ideal_cycle_time_minutes_per_unit', 16, 10)->nullable();
            $table->decimal('total_output', 15, 3)->nullable();
            $table->decimal('reject_output', 15, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'process_type',
                'output_unit',
                'theoretical_output',
                'ideal_cycle_time_minutes_per_unit',
                'total_output',
                'reject_output',
            ]);
        });

        Schema::table('oee_standards', function (Blueprint $table) {
            $table->dropColumn([
                'process_type',
                'output_unit',
                'planned_time_minutes',
                'theoretical_output',
                'ideal_cycle_time_minutes_per_unit',
            ]);
        });
    }
};
