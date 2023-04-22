<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bgpPeers', function (Blueprint $table) {
            $table->unsignedInteger('bgpPeerIface')->nullable()->after('bgpPeerLastErrorText');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bgpPeers', function (Blueprint $table) {
            $table->dropColumn(['bgpPeerIface']);
        });
    }
};
