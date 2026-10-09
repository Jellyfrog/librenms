<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $valid = ['ok', 'warning', 'critical'];

        // invalid rule severity becomes critical so alerts are not hidden
        DB::table('alert_rules')->whereNotIn('severity', $valid)->update(['severity' => 'critical']);
        DB::table('alert_faults')->whereNotIn('severity', $valid)->update(['severity' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
