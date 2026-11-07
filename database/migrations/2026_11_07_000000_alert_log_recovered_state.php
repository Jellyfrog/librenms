<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recoveries were logged with state 0 (AlertState::RECOVERED, which equals CLEAR).
     * alert_log entries are only written for transitions, so 0 was never a separate "clear"
     * entry: every one of them is a recovery (AlertLogState::Recovered = 6).
     */
    public function up(): void
    {
        DB::table('alert_log')->where('state', 0)->update(['state' => 6]);
    }

    public function down(): void
    {
        DB::table('alert_log')->where('state', 6)->update(['state' => 0]);
    }
};
