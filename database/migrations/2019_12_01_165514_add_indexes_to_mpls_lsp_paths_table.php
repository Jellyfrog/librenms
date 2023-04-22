<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mpls_lsp_paths', function (Blueprint $table) {
            /** add
             * vRtrMplsLspPathTunnelARHopListIndex
             * vRtrMplsLspPathTunnelCHopListIndex
             * indexes to table
             */
            $table->unsignedInteger('mplsLspPathTunnelARHopListIndex')->nullable();
            $table->unsignedInteger('mplsLspPathTunnelCHopListIndex')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mpls_lsp_paths', function (Blueprint $table) {
            $table->dropColumn(['mplsLspPathTunnelARHopListIndex', 'mplsLspPathTunnelCHopListIndex']);
        });
    }
};
