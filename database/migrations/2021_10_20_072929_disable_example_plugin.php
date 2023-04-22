<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // disable ExamplePlugin that was accidentally enabled for everyone
        DB::table('plugins')
            ->where([
                'plugin_name' => 'ExamplePlugin',
                'version' => 2,
            ])->update([
                'plugin_active' => 0,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
