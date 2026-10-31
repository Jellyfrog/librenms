<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COUNTERS = [
        'rate_95th_in',
        'rate_95th_out',
        'rate_95th',
        'total_data',
        'total_data_in',
        'total_data_out',
        'rate_average_in',
        'rate_average_out',
        'rate_average',
    ];

    /**
     * New bills only set their settings, the calculated values were filled in by INSERT IGNORE on MySQL
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            foreach (self::COUNTERS as $column) {
                $table->bigInteger($column)->default(0)->change();
            }
            $table->dateTime('bill_last_calc')->nullable()->change();
            $table->boolean('bill_autoadded')->default(0)->change();
        });
    }

    public function down(): void
    {
        DB::table('bills')->whereNull('bill_last_calc')->update(['bill_last_calc' => '1970-01-02 00:00:00']);

        Schema::table('bills', function (Blueprint $table): void {
            foreach (self::COUNTERS as $column) {
                $table->bigInteger($column)->default(null)->change();
            }
            $table->dateTime('bill_last_calc')->nullable(false)->change();
            $table->boolean('bill_autoadded')->default(null)->change();
        });
    }
};
