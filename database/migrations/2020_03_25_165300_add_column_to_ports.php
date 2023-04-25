<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ports', function (Blueprint $table) {
            $table->bigInteger('ifSpeed_prev')->nullable()->after('ifSpeed');
            $table->integer('ifHighSpeed_prev')->nullable()->after('ifHighSpeed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ports', function (Blueprint $table) {
            $table->dropColumn(['ifSpeed_prev', 'ifHighSpeed_prev']);
        });
    }
};
