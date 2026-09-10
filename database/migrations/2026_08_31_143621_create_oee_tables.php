<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('area_name');
            $table->timestamps();
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->string('machine_name');
            $table->timestamps();
        });

        Schema::create('downtime_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->timestamps();
        });

        Schema::create('personnels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role'); 
            $table->timestamps();
        });

        Schema::create('production_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('personnels')->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('planned_time_minutes')->default(480);
            $table->decimal('total_output_pcs', 12, 2)->default(0);
            $table->decimal('reject_pcs', 12, 2)->default(0);
            $table->enum('status', ['running', 'downtime', 'completed'])->default('running');
            $table->timestamps();
        });

        Schema::create('downtime_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_session_id')->constrained()->onDelete('cascade');
            $table->dateTime('stop_time');
            $table->dateTime('go_time')->nullable();
            $table->integer('duration_seconds')->default(0);
            // Dibuat nullable agar bisa kosong saat tombol STOP ditekan
            $table->foreignId('downtime_category_id')->nullable()->constrained();
            $table->foreignId('personnel_id')->nullable()->constrained('personnels');
            $table->string('replaced_component')->nullable(); 
            $table->string('repair_action')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downtime_logs');
        Schema::dropIfExists('production_sessions');
        Schema::dropIfExists('personnels');
        Schema::dropIfExists('downtime_categories');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('areas');
    }
};