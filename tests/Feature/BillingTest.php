<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Bill;
use App\Models\BillData;
use App\Models\BillHistory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LibreNMS\Billing;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->start = (int) strtotime('2026-01-15 12:00:00');
});

test('get rates totals and averages', function () {
    $bill = Bill::factory()->create();
    addSample($bill, $this->start + 300, 300, 100, 1000);
    addSample($bill, $this->start + 600, 300, 200, 2000);

    $rates = Billing::getRates($bill->bill_id, dbDate($this->start), dbDate($this->start + 600), 'agg');

    expect($rates['total_data'])->toEqual(3300);
    expect($rates['total_data_in'])->toEqual(300);
    expect($rates['total_data_out'])->toEqual(3000);
    expect($rates['rate_average'])->toEqual(44);
    expect($rates['rate_average_in'])->toEqual(4);
    expect($rates['rate_average_out'])->toEqual(40);
});

test('get rates returns the95th percentile rates', function () {
    $bill = Bill::factory()->create();
    foreach (range(1, 100) as $n) {
        // 8n bps in and 16n bps out
        addSample($bill, $this->start + $n * 300, 300, 300 * $n, 600 * $n);
    }

    $rates = Billing::getRates($bill->bill_id, dbDate($this->start), dbDate($this->start + 30000), 'agg');

    expect($rates['rate_95th_in'])->toEqual(752);
    expect($rates['rate_95th_out'])->toEqual(1504);
    expect($rates['rate_95th'])->toEqual(2256);
    expect($rates['dir_95th'])->toEqual('agg');
});

test('get rates uses the higher direction', function () {
    $out = Bill::factory()->create();
    $in = Bill::factory()->create();
    foreach (range(1, 40) as $n) {
        addSample($out, $this->start + $n * 300, 300, 300 * $n, 600 * $n);
        addSample($in, $this->start + $n * 300, 300, 600 * $n, 300 * $n);
    }
    $from = dbDate($this->start);
    $to = dbDate($this->start + 12000);

    $rates = Billing::getRates($out->bill_id, $from, $to, 'max');
    expect($rates['dir_95th'])->toEqual('out');
    expect($rates['rate_95th'])->toEqual($rates['rate_95th_out']);
    expect($rates['rate_95th'])->toBeGreaterThan($rates['rate_95th_in']);

    $rates = Billing::getRates($in->bill_id, $from, $to, 'max');
    expect($rates['dir_95th'])->toEqual('in');
    expect($rates['rate_95th'])->toEqual($rates['rate_95th_in']);
    expect($rates['rate_95th'])->toBeGreaterThan($rates['rate_95th_out']);
});

test('get rates combines samples in afive minute bucket', function () {
    $bill = Bill::factory()->create();
    addSample($bill, $this->start + 10, 150, 300, 600);
    addSample($bill, $this->start + 160, 150, 600, 1200);

    $rates = Billing::getRates($bill->bill_id, dbDate($this->start), dbDate($this->start + 299), 'agg');

    expect($rates['rate_95th_in'])->toEqual(24);
    expect($rates['rate_95th_out'])->toEqual(48);
    expect($rates['rate_95th'])->toEqual(72);
});

test('get rates with no data', function () {
    $bill = Bill::factory()->create();

    $rates = Billing::getRates($bill->bill_id, dbDate($this->start), dbDate($this->start + 300), 'max');

    expect($rates['rate_95th'])->toEqual(0);
    expect($rates['dir_95th'])->toEqual('in');
    expect($rates['total_data'])->toBeNull();
    expect($rates['rate_average'])->toEqual(0);
});

test('billing period excludes start and includes end', function () {
    $bill = Bill::factory()->create();
    addSample($bill, $this->start, 300, 100, 1000);
    addSample($bill, $this->start + 300, 300, 200, 2000);
    addSample($bill, $this->start + 600, 300, 300, 3000);
    addSample($bill, $this->start + 900, 300, 400, 4000);
    $from = dbDate($this->start);
    $to = dbDate($this->start + 600);

    $period = Billing::getPeriod($bill->bill_id, $from, $to);
    expect($period['period'])->toEqual(600);
    expect($period['peak_in'])->toEqual(300);
    expect($period['peak_out'])->toEqual(3000);

    expect(Billing::getRates($bill->bill_id, $from, $to, 'agg')['total_data'])->toEqual(5500);
});

test('get period ignores other bills', function () {
    $bill = Bill::factory()->create();
    $other = Bill::factory()->create();
    addSample($bill, $this->start + 300, 300, 100, 1000);
    addSample($other, $this->start + 300, 900, 500, 5000);

    $period = Billing::getPeriod($bill->bill_id, dbDate($this->start), dbDate($this->start + 300));

    expect($period['period'])->toEqual(300);
    expect($period['peak_in'])->toEqual(100);
    expect($period['peak_out'])->toEqual(1000);
});

test('get period with no data', function () {
    $bill = Bill::factory()->create();

    $period = Billing::getPeriod($bill->bill_id, dbDate($this->start), dbDate($this->start + 300));

    expect($period)->toBe(['period' => null, 'peak_in' => null, 'peak_out' => null]);
});

test('bits graph data reduces points', function () {
    $bill = Bill::factory()->create(['bill_type' => 'cdr', 'rate_95th' => 123, 'rate_average' => 45]);
    addRamp($bill, 4);

    $graph = Billing::getBitsGraphData($bill->bill_id, $this->start, $this->start + 1200, 2);

    expect(array_values($graph['in_data']))->toEqual([12, 28]);
    expect(array_values($graph['out_data']))->toEqual([24, 56]);
    expect(array_values($graph['tot_data']))->toEqual([36, 84]);
    expect(array_values($graph['ticks']))->toEqual([$this->start + 600, $this->start + 1200]);
    expect($graph['first'])->toEqual($this->start + 300);
    expect($graph['last'])->toEqual($this->start + 1200);
    expect($graph['max_in'])->toEqual(32);
    expect($graph['max_out'])->toEqual(64);
    expect($graph['ave_in'])->toEqual(20);
    expect($graph['ave_out'])->toEqual(40);
    expect($graph['last_in'])->toEqual(32);
    expect($graph['last_out'])->toEqual(64);
    expect($graph['rate_95th'])->toEqual(123);
    expect($graph['rate_average'])->toEqual(45);
    expect($graph['bill_type'])->toEqual('cdr');
});

test('bits graph data writes leftover samples', function () {
    $bill = Bill::factory()->create();
    addRamp($bill, 4);

    $graph = Billing::getBitsGraphData($bill->bill_id, $this->start, $this->start + 1200, 3);

    expect(array_values($graph['in_data']))->toEqual([16, 32]);
    expect(array_values($graph['ticks']))->toEqual([$this->start + 900, $this->start + 1200]);
});

test('bits graph data includes both ends', function () {
    $bill = Bill::factory()->create();
    addRamp($bill, 4);

    $graph = Billing::getBitsGraphData($bill->bill_id, $this->start + 600, $this->start + 900, 1);

    expect(array_values($graph['in_data']))->toEqual([16, 24]);
    expect($graph['first'])->toEqual($this->start + 600);
    expect($graph['last'])->toEqual($this->start + 900);
});

test('bits graph data auto reduce factor', function () {
    $bill = Bill::factory()->create();
    addRamp($bill, 4);

    $graph = Billing::getBitsGraphData($bill->bill_id, $this->start, $this->start + 1200, null);

    expect($graph['ticks'])->toHaveCount(4);
});

test('bits graph data keeps leftover outbound only sample', function () {
    $bill = Bill::factory()->create();
    addRamp($bill, 2);
    addSample($bill, $this->start + 900, 300, 0, 300);

    $graph = Billing::getBitsGraphData($bill->bill_id, $this->start, $this->start + 900, 2);

    expect(array_values($graph['in_data']))->toEqual([12, 0]);
    expect(array_values($graph['out_data']))->toEqual([24, 8]);
});

test('bits graph data survives trailing zero period', function () {
    $bill = Bill::factory()->create();

    // poll-billing.php writes period 0 for the first sample of a bill
    addSample($bill, $this->start, 0, 100, 100);

    $graph = Billing::getBitsGraphData($bill->bill_id, $this->start - 10, $this->start + 10, 1);

    expect($graph['ticks'])->toBe([]);
    expect($graph['first'])->toEqual($this->start);
});

test('bits graph data for missing bill', function () {
    $this->expectException(ModelNotFoundException::class);

    Billing::getBitsGraphData(999999, $this->start, $this->start + 300, 1);
});

test('history bits graph data uses historical rates', function () {
    $bill = Bill::factory()->create(['bill_type' => 'quota', 'rate_95th' => 1, 'rate_average' => 2]);
    addRamp($bill, 4);
    $history = addHistory($bill, $this->start, $this->start + 600, ['bill_type' => 'CDR', 'rate_95th' => 500, 'rate_average' => 400]);

    $graph = Billing::getHistoryBitsGraphData($bill->bill_id, $history->bill_hist_id, 1);

    expect($graph)->not->toBeNull();
    expect($graph['from'])->toEqual($this->start);
    expect($graph['to'])->toEqual($this->start + 600);
    expect($graph['ticks'])->toHaveCount(2);
    expect($graph['rate_95th'])->toEqual(500);
    expect($graph['rate_average'])->toEqual(400);
    expect($graph['bill_type'])->toEqual('CDR');
});

test('history bits graph data for missing history', function () {
    $bill = Bill::factory()->create();

    expect(Billing::getHistoryBitsGraphData($bill->bill_id, 999999, 1))->toBeNull();
});

test('historic transfer graph data limits to newest twelve', function () {
    $bill = Bill::factory()->create();
    foreach (range(1, 13) as $month) {
        addHistory($bill, (int) mktime(0, 0, 0, $month, 1, 2025), (int) mktime(0, 0, 0, $month, 20, 2025), [
            'bill_type' => $month % 2 ? 'Quota' : 'CDR',
            'bill_allowed' => 1000,
            'bill_overuse' => 50,
            'traf_in' => $month,
            'traf_out' => $month * 10,
            'traf_total' => $month * 11,
        ]);
    }

    $graph = Billing::getHistoricTransferGraphData($bill->bill_id);

    expect($graph['in_data'])->toHaveCount(12);
    expect($graph['ticklabels'][0])->toEqual('2026-01-01 - 2026-01-20');
    expect($graph['in_data'][0])->toEqual(13);
    expect($graph['out_data'][0])->toEqual(130);
    expect($graph['tot_data'][0])->toEqual(143);
    expect($graph['in_data'][11])->toEqual(2);
    expect($graph['allow_data'][0])->toEqual(1000);
    expect($graph['overuse_data'][0])->toEqual(50);
    expect($graph['allow_data'][1])->toEqual(0);
    expect($graph['overuse_data'][1])->toEqual(0);
});

test('historic transfer graph data pads to twelve', function () {
    $bill = Bill::factory()->create();
    addHistory($bill, $this->start, $this->start + 600, ['bill_type' => 'Quota', 'bill_allowed' => 1000, 'traf_in' => 5]);

    $graph = Billing::getHistoricTransferGraphData($bill->bill_id);

    expect($graph['in_data'])->toHaveCount(12);
    expect($graph['ticklabels'])->toHaveCount(12);
    expect($graph['in_data'][0])->toEqual(5);
    expect($graph['allow_data'][1])->toEqual(1000);
    expect($graph['allow_data'][2])->toEqual(0);
    expect($graph['ticklabels'][1])->toBe('');
});

test('bandwidth graph data by day', function () {
    $bill = Bill::factory()->create();
    $dayOne = (int) strtotime('2026-01-15 12:00:00');
    $dayTwo = (int) strtotime('2026-01-16 12:00:00');
    addSample($bill, $dayOne, 300, 100, 200);
    addSample($bill, $dayOne + 3600, 300, 100, 200);
    addSample($bill, $dayTwo, 300, 300, 400);

    $graph = Billing::getBandwidthGraphData($bill->bill_id, null, $dayOne - 3600, $dayTwo + 3600, 'day');

    expect($graph)->not->toBeNull();
    expect(array_slice($graph['ticklabels'], 0, 2))->toEqual(['2026-01-15', '2026-01-16']);
    expect(array_slice($graph['in_data'], 0, 2))->toEqual([200, 300]);
    expect(array_slice($graph['out_data'], 0, 2))->toEqual([400, 400]);
    expect(array_slice($graph['tot_data'], 0, 2))->toEqual([600, 700]);
    expect($graph['ave_data'][0])->toEqual(650);
});

test('bandwidth graph data by hour', function () {
    $bill = Bill::factory()->create();
    $noon = (int) strtotime('2026-01-15 12:00:00');
    addSample($bill, $noon, 300, 100, 200);
    addSample($bill, $noon + 3600, 300, 300, 400);
    addSample($bill, $noon + 86400, 300, 500, 600);

    $graph = Billing::getBandwidthGraphData($bill->bill_id, null, $noon, $noon + 86400, 'hour');

    expect($graph)->not->toBeNull();
    expect($graph['ticklabels'])->toEqual(['12:00', '13:00']);
    expect($graph['in_data'])->toEqual([600, 300]);
    expect($graph['out_data'])->toEqual([800, 400]);
    expect($graph['tot_data'])->toEqual([1400, 700]);
    expect($graph['ave_data'][0])->toEqual(1050);
});

test('bandwidth graph data uses history range', function () {
    $bill = Bill::factory()->create();
    $noon = (int) strtotime('2026-01-15 12:00:00');
    addSample($bill, $noon, 300, 100, 200);
    addSample($bill, $noon + 7200, 300, 500, 600);
    $history = addHistory($bill, $noon, $noon + 3600);

    $graph = Billing::getBandwidthGraphData($bill->bill_id, $history->bill_hist_id, null, null, 'hour');

    expect($graph)->not->toBeNull();
    expect($graph['ticklabels'])->toEqual(['12:00']);
    expect($graph['in_data'])->toEqual([100]);
});

test('bandwidth graph data for missing history', function () {
    $bill = Bill::factory()->create();

    expect(Billing::getBandwidthGraphData($bill->bill_id, 999999, null, null, 'hour'))->toBeNull();
});

test('bandwidth graph data requires range', function () {
    $bill = Bill::factory()->create();

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Must supply from and to');

    Billing::getBandwidthGraphData($bill->bill_id, null, null, null, 'hour');
});

test('bandwidth graph data rejects unknown type', function () {
    $bill = Bill::factory()->create();

    $this->expectException(InvalidArgumentException::class);

    Billing::getBandwidthGraphData($bill->bill_id, null, $this->start, $this->start + 300, '<script>');
});

/**
 * Samples every 5 minutes after the start at 8n bps in and 16n bps out
 */
function addRamp(Bill $bill, int $samples): void
{
    foreach (range(1, $samples) as $n) {
        addSample($bill, $this->start + $n * 300, 300, 300 * $n, 600 * $n);
    }
}

function addSample(Bill $bill, int $time, int $period, int $in, int $out): void
{
    BillData::insert([
        'bill_id' => $bill->bill_id,
        'timestamp' => date('Y-m-d H:i:s', $time),
        'period' => $period,
        'delta' => $in + $out,
        'in_delta' => $in,
        'out_delta' => $out,
    ]);
}

/**
 * @param  array<model-property<BillHistory>, int|string>  $attributes
 */
function addHistory(Bill $bill, int $from, int $to, array $attributes = []): BillHistory
{
    return BillHistory::create(array_merge([
        'bill_id' => $bill->bill_id,
        'bill_datefrom' => date('Y-m-d H:i:s', $from),
        'bill_dateto' => date('Y-m-d H:i:s', $to),
        'bill_type' => 'CDR',
        'bill_allowed' => 0,
        'bill_used' => 0,
        'bill_overuse' => 0,
        'bill_percent' => 0,
        'rate_95th_in' => 0,
        'rate_95th_out' => 0,
        'rate_95th' => 0,
        'dir_95th' => 'in',
        'rate_average' => 0,
        'rate_average_in' => 0,
        'rate_average_out' => 0,
        'traf_in' => 0,
        'traf_out' => 0,
        'traf_total' => 0,
        'bill_peak_out' => 0,
        'bill_peak_in' => 0,
    ], $attributes));
}

function dbDate(int $time): string
{
    return date('YmdHis', $time);
}