<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\Device;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;


beforeEach(function () {
    Device::factory()->create(['hostname' => 'amber.example.com']);
    Device::factory()->create(['hostname' => 'amethyst.example.com']);
    Device::factory()->create(['hostname' => 'bronze.example.com']);
});

afterEach(function () {
    putenv('COMP_LINE');
    putenv('COMP_CURRENT');
    putenv('COMP_PREVIOUS');

});

/**
 * @return array<int, array{string}>
 */
dataset('deviceCommands', function () {
    return [
        ['device:poll'],
        ['device:discover'],
        ['device:ping'],
        ['device:remove'],
        ['device:rename'],
        ['port:tune'],
        ['snmp:get'],
        ['report:devices'],
    ];
});

test('completes device hostname prefix', function (string $command) {
    expect(complete("lnms $command am", 'am', $command))->toBe(['amber.example.com', 'amethyst.example.com']);
})->with('deviceCommands');

test('completes all devices when empty', function () {
    expect(complete('lnms device:poll ', '', 'device:poll'))->toBe(['amber.example.com', 'amethyst.example.com', 'bronze.example.com']);
});

test('does not complete device for option value', function () {
    expect(complete('lnms device:poll amber.example.com -m am', 'am', '-m'))->not->toContain('amber.example.com');
});

/**
 * @return array<int, array{string, string, string[]|null}>
 */
dataset('optionValueCommands', function () {
    return [
        ['lnms report:devices -o ', '-o', ['table', 'csv', 'json', 'none']],
        ['lnms device:poll -m ', '-m', null],
        ['lnms device:poll --os ', '--os', null],
        ['lnms device:discover -m ', '-m', null],
        ['lnms device:ping -g ', '-g', null],
    ];
});

test('does not complete device for empty option value before device spec', function (string $line, string $previous, ?array $expected) {
    $completions = complete($line, '', $previous);

    expect($completions)->not->toContain('amber.example.com');
    if ($expected !== null) {
        expect($completions)->toBe($expected);
    }
})->with('optionValueCommands');

test('does not complete device rename new hostname', function () {
    expect(complete('lnms device:rename bronze.example.com am', 'am', 'bronze.example.com'))->not->toContain('amber.example.com');
});

/**
 * @return string[]
 */
function complete(string $line, string $current, string $previous): array
{
    putenv("COMP_LINE=$line");
    putenv("COMP_CURRENT=$current");
    putenv("COMP_PREVIOUS=$previous");

    ob_start();
    Artisan::call('list:bash-completion');
    $output = ob_get_clean();

    return array_values(array_filter(explode(PHP_EOL, $output)));
}
