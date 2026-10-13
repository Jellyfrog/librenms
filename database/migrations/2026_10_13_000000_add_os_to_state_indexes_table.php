<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('state_indexes', function (Blueprint $table): void {
            $table->string('os', 32)->default('')->after('state_index_id');
            $table->dropUnique('state_indexes_state_name_unique');
            $table->unique(['os', 'state_name']);
        });

        // state names were shared by every os, give each os using a state name its own copy
        DB::transaction(function (): void {
            $this->splitStateIndexesByOs();
        });
    }

    private function splitStateIndexesByOs(): void
    {
        $usage = DB::table('sensors_to_state_indexes')
            ->join('sensors', 'sensors.sensor_id', '=', 'sensors_to_state_indexes.sensor_id')
            ->leftJoin('devices', 'devices.device_id', '=', 'sensors.device_id')
            ->selectRaw("sensors_to_state_indexes.state_index_id, COALESCE(devices.os, '') as os")
            ->distinct()
            ->orderBy('os')
            ->get()
            ->groupBy('state_index_id');

        foreach (DB::table('state_indexes')->orderBy('state_index_id')->get() as $stateIndex) {
            $oses = $usage->get($stateIndex->state_index_id)?->pluck('os') ?? collect();

            if ($oses->isEmpty()) {
                // not used by any sensor
                DB::table('state_translations')->where('state_index_id', $stateIndex->state_index_id)->delete();
                DB::table('state_indexes')->where('state_index_id', $stateIndex->state_index_id)->delete();

                continue;
            }

            // sensors without a device os sort first and stay on the existing state index
            DB::table('state_indexes')->where('state_index_id', $stateIndex->state_index_id)->update(['os' => $oses->shift()]);

            $translations = DB::table('state_translations')
                ->where('state_index_id', $stateIndex->state_index_id)
                ->get(['state_descr', 'state_value', 'state_generic_value']);

            foreach ($oses as $os) {
                $newId = DB::table('state_indexes')->insertGetId(['os' => $os, 'state_name' => $stateIndex->state_name], 'state_index_id');

                DB::table('state_translations')->insert($translations->map(fn ($translation) => [...(array) $translation, 'state_index_id' => $newId])->all());

                DB::table('sensors_to_state_indexes')
                    ->where('state_index_id', $stateIndex->state_index_id)
                    ->whereIn('sensor_id', DB::table('sensors')
                        ->join('devices', 'devices.device_id', '=', 'sensors.device_id')
                        ->where('devices.os', $os)
                        ->select('sensors.sensor_id'))
                    ->update(['state_index_id' => $newId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // merge the per os copies back into one state index per state name
        foreach (DB::table('state_indexes')->orderBy('state_index_id')->get()->groupBy(fn ($stateIndex) => strtolower((string) $stateIndex->state_name)) as $stateIndexes) {
            $keep = $stateIndexes->shift()->state_index_id;
            $duplicates = $stateIndexes->pluck('state_index_id');

            if ($duplicates->isNotEmpty()) {
                DB::table('sensors_to_state_indexes')->whereIn('state_index_id', $duplicates)->update(['state_index_id' => $keep]);
                DB::table('state_translations')->whereIn('state_index_id', $duplicates)->delete();
                DB::table('state_indexes')->whereIn('state_index_id', $duplicates)->delete();
            }
        }

        Schema::table('state_indexes', function (Blueprint $table): void {
            $table->dropUnique(['os', 'state_name']);
            $table->unique('state_name');
            $table->dropColumn('os');
        });
    }
};
