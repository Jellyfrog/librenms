<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;


beforeEach(function () {
    $this->previousConnection = DB::getDefaultConnection();
    config(['database.connections.cache_migration' => array_replace(config('database.connections.testing'), [
        'prefix' => 'cache_expiration_test_',
        'prefix_indexes' => false,
    ])]);
    DB::setDefaultConnection('cache_migration');

    (require base_path('database/migrations/2020_09_18_223431_create_cache_table.php'))->up();
    (require base_path('database/migrations/2020_09_24_000500_create_cache_locks_table.php'))->up();
});

afterEach(function () {
    Schema::dropIfExists('cache_locks');
    Schema::dropIfExists('cache');
    DB::purge('cache_migration');
    DB::setDefaultConnection($this->previousConnection);

});

test('missing expiration indexes are added without duplicating existing ones', function (array $indexedTables) {
    foreach ($indexedTables as $tableName) {
        Schema::table($tableName, function (Blueprint $table): void {
            $table->index('expiration');
        });
    }

    migration()->up();

    expect(Schema::hasIndex('cache', 'cache_expiration_index'))->toBeTrue();
    expect(Schema::hasIndex('cache_locks', 'cache_locks_expiration_index'))->toBeTrue();
})->with('existingIndexes');

test('legacy lock table uses dynamic row format and keeps locks', function () {
    if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('InnoDB row formats require MySQL or MariaDB.');
    }

    DB::statement('ALTER TABLE `cache_expiration_test_cache_locks` MODIFY `key` VARCHAR(255) CHARACTER SET utf8mb3 NOT NULL, ROW_FORMAT=COMPACT');
    $lock = ['key' => str_repeat('a', 255), 'owner' => 'existing-owner', 'expiration' => 2000000000];
    DB::table('cache_locks')->insert($lock);

    migration()->up();

    $status = DB::selectOne('SHOW TABLE STATUS WHERE Name = ?', ['cache_expiration_test_cache_locks']);
    expect($status->Row_format)->toBe('Dynamic');
    expect(Schema::hasIndex('cache_locks', 'cache_locks_expiration_index'))->toBeTrue();
    $this->assertDatabaseHas('cache_locks', $lock);
});

/**
 * @return array<string, array{list<string>}>
 */
dataset('existingIndexes', function () {
    return [
        'neither index exists' => [[]],
        'cache index exists' => [['cache']],
        'cache locks index exists' => [['cache_locks']],
        'both indexes exist' => [['cache', 'cache_locks']],
    ];
});

function migration(): object
{
    return require base_path('database/migrations/2026_09_22_000001_add_expiration_index_to_cache_table.php');
}