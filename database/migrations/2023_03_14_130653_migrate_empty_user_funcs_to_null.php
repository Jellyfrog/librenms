<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \App\Models\Sensor::where('user_func', '')->update(['user_func' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
