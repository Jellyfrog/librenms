<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use LibreNMS\DB\BulkTableSync;
use LibreNMS\Interfaces\Models\Keyable;

uses(LibreNMS\Tests\TestCase::class);

beforeEach(function (): void {
    config(['database.connections.bulk_sync' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    $this->db = DB::connection('bulk_sync');
    $this->db->getSchemaBuilder()->create('entries', function (Blueprint $table): void {
        $table->increments('id');
        $table->integer('scope');
        $table->string('name')->nullable();
        $table->string('group')->nullable();
        $table->string('value')->nullable();
        $table->integer('score')->nullable();
        $table->string('protected')->default('keep');
        $table->timestamps();
    });
});

afterEach(function (): void {
    DB::purge('bulk_sync');
});

function bulkSync(int $scope = 1, string $lookup = 'name'): BulkTableSync
{
    $parent = new class extends Model {};
    $parent->setConnection('bulk_sync');
    $parent->setRawAttributes(['id' => $scope]);

    return new BulkTableSync($parent->hasMany(BulkSyncEntry::class, 'scope'), $lookup);
}

test('history and other scopes are untouched and creation time is preserved', function (): void {
    $rows = [['name' => 'a', 'group' => null, 'value' => 'old'], ['name' => 'b', 'group' => null, 'value' => 'history']];
    bulkSync()->sync($rows, '2026-01-01 00:00:00');
    bulkSync(2)->sync($rows, '2026-01-01 00:00:00');
    $before = $this->db->table('entries')->orderBy('id')->get();

    $counts = bulkSync()->sync([['name' => 'a', 'group' => null, 'value' => 'new', 'protected' => 'overwrite', 'scope' => 2]], '2026-01-02 00:00:00');

    $after = $this->db->table('entries')->orderBy('id')->get();
    expect($counts)->toBe(['inserted' => 0, 'updated' => 1, 'unchanged' => 0, 'deleted' => 0]);
    expect($after[0]->id)->toBe($before[0]->id);
    expect($after[0]->created_at)->toBe($before[0]->created_at);
    expect($after[0]->updated_at)->toBe('2026-01-02 00:00:00');
    expect($after[0]->value)->toBe('new');
    expect($after[0]->protected)->toBe('keep');
    expect($after[0]->scope)->toBe(1);
    foreach ([1, 2, 3] as $index) {
        expect($after[$index])->toEqual($before[$index]);
    }
});

test('null keys are distinct from empty strings and zero and do not escape scope', function (): void {
    $rows = [];
    foreach ([null, '', '0'] as $group) {
        $rows[] = ['name' => 'a', 'group' => $group, 'value' => 'first'];
    }
    bulkSync()->sync($rows);
    bulkSync(2)->sync($rows);
    $rows = array_map(fn ($row) => array_replace($row, ['value' => 'second']), $rows);
    $counts = bulkSync()->sync($rows);

    expect($counts['updated'])->toBe(3);
    expect($this->db->table('entries')->count())->toBe(6);
    expect($this->db->table('entries')->where('scope', 1)->where('value', 'second')->count())->toBe(3);
    expect($this->db->table('entries')->where('scope', 2)->where('value', 'first')->count())->toBe(3);
});

test('only observed duplicates are deleted and last input wins', function (): void {
    $row = ['scope' => 1, 'name' => 'a', 'group' => null, 'value' => 'old'];
    $id = $this->db->table('entries')->insertGetId($row);
    $this->db->table('entries')->insert([$row, $row, array_replace($row, ['group' => 'history']), array_replace($row, ['group' => 'history'])]);

    $counts = bulkSync()->sync([
        ['name' => 'a', 'group' => null, 'value' => 'ignored'],
        ['name' => 'a', 'group' => null, 'value' => 'last'],
    ]);

    expect($counts['deleted'])->toBe(2);
    expect($counts['updated'])->toBe(1);
    expect($this->db->table('entries')->where('id', $id)->value('value'))->toBe('last');
    expect($this->db->table('entries')->count())->toBe(3);
});

test('sparse updates and quoted values including null and zero', function (): void {
    bulkSync()->sync([
        ['name' => 'a', 'group' => 'g', 'value' => 'old', 'score' => 1],
        ['name' => 'b', 'group' => 'g', 'value' => 'keep', 'score' => 2],
        ['name' => 'c', 'group' => 'g', 'value' => 'old', 'score' => 3],
    ]);
    $quoted = "quote' ? \\ and unicode é";
    $counts = bulkSync()->sync([
        ['name' => 'a', 'group' => 'g', 'value' => null, 'score' => '0'],
        ['name' => 'b', 'group' => 'g', 'score' => null],
        ['name' => 'c', 'group' => 'g', 'value' => $quoted],
    ]);
    $rows = $this->db->table('entries')->get()->keyBy('name');

    expect($counts['updated'])->toBe(3);
    expect($rows['a']->value)->toBeNull();
    expect($rows['a']->score)->toBe(0);
    expect($rows['b']->value)->toBe('keep');
    expect($rows['b']->score)->toBeNull();
    expect($rows['c']->value)->toBe($quoted);
    expect($rows['c']->score)->toBe(3);
});

test('merge only changes writable values and unchanged rows are touched', function (): void {
    $row = ['name' => 'a', 'group' => 'g', 'value' => 'known', 'score' => 42];
    bulkSync()->sync([$row], '2026-01-01 00:00:00');
    $counts = bulkSync()->sync([array_replace($row, ['value' => null])], '2026-01-02 00:00:00',
        fn ($new, $old) => array_replace($new, ['value' => $old['value'], 'scope' => 2]));

    $entry = $this->db->table('entries')->sole();
    expect($counts['unchanged'])->toBe(1);
    expect($entry->name)->toBe('a');
    expect($entry->scope)->toBe(1);
    expect($entry->value)->toBe('known');
    expect($entry->updated_at)->toBe('2026-01-02 00:00:00');
});

test('batches large inputs and varied updates', function (): void {
    $rows = [];
    for ($i = 1; $i <= 2005; $i++) {
        $rows[] = ['name' => "entry-$i", 'group' => 'g', 'value' => 'initial', 'score' => $i];
    }
    $this->db->enableQueryLog();
    $counts = bulkSync()->sync($rows);
    expect($counts['inserted'])->toBe(2005);
    expect($this->db->getQueryLog())->toHaveCount(6); // three matching reads and three inserts
    $this->db->flushQueryLog();

    $rows = array_map(fn ($row) => array_replace($row, ['value' => $row['name'], 'score' => $row['score'] + 1]), $rows);
    $counts = bulkSync()->sync($rows);
    expect($counts['updated'])->toBe(2005);
    expect($this->db->getQueryLog())->toHaveCount(6);
    expect($this->db->table('entries')->count())->toBe(2005);
    expect($this->db->table('entries')->where('name', 'entry-2005')->value('score'))->toBe(2006);
});

test('empty input does no queries', function (): void {
    $this->db->enableQueryLog();
    expect(bulkSync()->sync([]))->toBe(['inserted' => 0, 'updated' => 0, 'unchanged' => 0, 'deleted' => 0]);
    expect($this->db->getQueryLog())->toHaveCount(0);
});

test('unchanged rows only touch last seen', function (): void {
    $rows = [
        ['name' => 'a', 'group' => 'g', 'value' => 'keep', 'score' => 0],
        ['name' => 'b', 'group' => 'g', 'value' => 'keep', 'score' => 0],
    ];
    bulkSync()->sync($rows, '2026-01-01 00:00:00');
    $this->db->enableQueryLog();
    $counts = bulkSync()->sync($rows, '2026-01-02 00:00:00');
    $queries = $this->db->getQueryLog();
    $this->db->disableQueryLog();

    expect($counts['unchanged'])->toBe(2);
    expect($queries)->toHaveCount(2);
    expect($queries[1]['query'])->toStartWith('update');
    $this->assertStringNotContainsString('"value"', $queries[1]['query']);
    $this->assertStringNotContainsString('"score"', $queries[1]['query']);
    expect($this->db->table('entries')->where('updated_at', '2026-01-02 00:00:00')->count())->toBe(2);
});

test('changed rows share one upsert and unchanged rows one update', function (): void {
    $rows = [];
    for ($i = 0; $i < 30; $i++) {
        $rows[] = ['name' => "entry-$i", 'group' => 'g', 'value' => 'keep', 'score' => 0];
    }
    bulkSync()->sync($rows, '2026-01-01 00:00:00');
    $ids = $this->db->table('entries')->pluck('id')->all();
    foreach ($rows as $i => &$row) {
        $row['score'] = $i < 2 ? 0 : ($i < 17 ? 100 : intdiv($i, 3));
    }
    unset($row);
    $this->db->enableQueryLog();
    $counts = bulkSync()->sync($rows, '2026-01-02 00:00:00');
    $queries = $this->db->getQueryLog();
    $this->db->disableQueryLog();

    expect($counts['updated'])->toBe(28);
    expect($counts['unchanged'])->toBe(2);
    expect($queries)->toHaveCount(3); // one read, one last-seen update, one upsert
    expect($queries[1]['query'])->toStartWith('update');
    expect($queries[2]['query'])->toStartWith('insert');
    expect($this->db->table('entries')->pluck('id')->all())->toBe($ids);
    expect($this->db->table('entries')->where('value', 'keep')
        ->where('created_at', '2026-01-01 00:00:00')->where('updated_at', '2026-01-02 00:00:00')->count())->toBe(30);
    foreach ($rows as $row) {
        expect($this->db->table('entries')->where('name', $row['name'])->value('score'))->toBe($row['score']);
    }
});

test('null lookup value is rejected', function (): void {
    $this->expectException(InvalidArgumentException::class);
    bulkSync()->sync([['name' => null, 'group' => 'g']]);
});

test('lookup column must be writable', function (): void {
    $this->expectException(InvalidArgumentException::class);
    bulkSync(lookup: 'scope');
});

test('model without timestamps is rejected', function (): void {
    $parent = new class extends Model {};
    $this->expectException(InvalidArgumentException::class);
    new BulkTableSync($parent->hasMany(BulkSyncEntryWithoutTimestamps::class, 'scope'), 'name');
});

test('booleans compare as integers', function (): void {
    bulkSync()->sync([['name' => 'a', 'group' => 'g', 'score' => false]]);
    $counts = bulkSync()->sync([['name' => 'a', 'group' => 'g', 'score' => false]]);

    expect($counts['unchanged'])->toBe(1);
    expect($this->db->table('entries')->value('score'))->toBe(0);
});

class BulkSyncEntry extends Model implements Keyable
{
    protected $table = 'entries';
    protected $fillable = ['name', 'group', 'value', 'score'];

    public function getCompositeKey(): string
    {
        return json_encode([$this->getAttribute('group'), $this->getAttribute('name')], JSON_THROW_ON_ERROR);
    }
}

class BulkSyncEntryWithoutTimestamps extends BulkSyncEntry
{
    public $timestamps = false;
}
