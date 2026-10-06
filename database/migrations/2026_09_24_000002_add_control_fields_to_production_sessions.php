<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('machine_id')->constrained()->restrictOnDelete();
            $table->foreignId('operator_id')->nullable()->change();
            $table->string('operator_name')->nullable()->after('operator_id');
            $table->char('controller_token_hash', 64)->nullable()->unique()->after('operator_name');
            $table->index(['machine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropIndex(['machine_id', 'status']);
            $table->dropUnique(['controller_token_hash']);
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn(['operator_name', 'controller_token_hash']);
            $table->foreignId('operator_id')->nullable(false)->change();
        });
    }
};
