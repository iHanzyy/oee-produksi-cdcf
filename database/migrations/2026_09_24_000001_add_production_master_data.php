<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['area_id', 'product_name']);
            $table->index(['area_id', 'is_active']);
        });

        Schema::table('machines', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('machine_name');
            $table->unique(['area_id', 'machine_name']);
            $table->index(['area_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropUnique(['area_id', 'machine_name']);
            $table->dropIndex(['area_id', 'is_active']);
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('products');
    }
};
