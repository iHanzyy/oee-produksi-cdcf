<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_sessions', function (Blueprint $table) {
            $table->dropColumn('batch_number');
        });
    }
};
