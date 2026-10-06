<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every product an owner so "my products" is a real query.
     */
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->foreignId('producer_id')
                ->nullable()
                ->after('category_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producer_id');
        });
    }
};
