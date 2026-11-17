<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Console\Commands\DevGenerateTestData;

test('requires explicit scope', function () {
    $this->artisan('dev:generate-test-data')
        ->expectsOutput('Specify an OS (or all).')
        ->assertExitCode(1);
});

test('accepts os as argument', function () {
    $this->artisan('dev:generate-test-data', ['prefix' => 'nonexistent-os-xyz'])
        ->expectsOutput('No matching JSON test fixtures found for prefix "nonexistent-os-xyz".')
        ->assertExitCode(1);
});

test('os argument completion includes all and os list', function () {
    $command = new DevGenerateTestData;

    $completions = $command->completeArgument('prefix', 'al');
    expect($completions)->toBeArray();
    expect($completions)->toContain('all');

    $completions = $command->completeArgument('prefix', 'io');
    expect($completions)->toBeArray();
    expect($completions)->toContain('ios');
    expect($completions)->not->toContain('all');
});

test('prefix matches fixture names', function () {
    $command = new DevGenerateTestData;
    $completions = $command->completeArgument('prefix', 'ios_');
    expect($completions)->toBeArray();
    expect($completions)->not->toContain('ios');
    foreach ($completions as $completion) {
        expect($completion)->toStartWith('ios_');
    }
});

test('prefix completion includes snmprec without json', function () {
    $command = new DevGenerateTestData;
    $completions = $command->completeArgument('prefix', '3com_');

    expect($completions)->toBeArray();
    expect($completions)->toContain('3com_1');
});
