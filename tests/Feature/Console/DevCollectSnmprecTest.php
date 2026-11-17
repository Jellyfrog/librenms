<?php

use App\Console\Commands\DevCollectSnmprec;
use App\Console\DynamicInputOption;
use App\Models\Device;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('help output', function (): void {
    $this->artisan('dev:collect-snmprec', ['--help' => true])
        ->assertExitCode(0);
});

test('device required without interaction', function (): void {
    $this->artisan('dev:collect-snmprec', ['--model' => 'test', '--no-interaction' => true])
        ->expectsOutput('Specify the device, for example lnms dev:collect-snmprec 42.')
        ->assertExitCode(1);
});

test('asks for device', function (): void {
    $device = Device::factory()->create(['os' => 'generic']);

    $this->artisan('dev:collect-snmprec', ['--model' => 'test'])
        ->expectsQuestion('Which device do you want to collect data from?', (string) $device->device_id)
        ->expectsOutput('The device is detected as generic. Add detection for its OS first, then collect the test data. To get a full walk to work from, use --full --output=<file>.')
        ->assertExitCode(1);
});

test('missing device', function (): void {
    $this->artisan('dev:collect-snmprec', ['device' => 'non_existent_device_99999', '--model' => 'test'])
        ->expectsOutput("Device 'non_existent_device_99999' not found.")
        ->assertExitCode(1);
});

test('missing model option', function (): void {
    $device = Device::factory()->create();

    $this->artisan('dev:collect-snmprec', ['device' => (string) $device->device_id, '--no-interaction' => true])
        ->expectsOutput('Specify the device model, for example -M wrt54g.')
        ->assertExitCode(1);
});

test('invalid model with underscore', function (): void {
    $device = Device::factory()->create();

    $this->artisan('dev:collect-snmprec', ['device' => (string) $device->device_id, '--model' => 'invalid_model'])
        ->expectsOutput('Model name can only contain letters, numbers, dots (.), plus (+), and dashes (-).')
        ->assertExitCode(1);
});

test('invalid model characters', function (): void {
    $this->artisan('dev:collect-snmprec', ['device' => 'unused', '--model' => '../foo'])
        ->expectsOutput('Model name can only contain letters, numbers, dots (.), plus (+), and dashes (-).')
        ->assertExitCode(1);
});

test('invalid module', function (): void {
    $this->artisan('dev:collect-snmprec', ['device' => 'unused', '--model' => 'test', '--modules' => 'nope'])
        ->expectsOutput('Invalid module name: nope')
        ->assertExitCode(1);
});

test('generic device is rejected', function (): void {
    $device = Device::factory()->create(['os' => 'generic']);

    $this->artisan('dev:collect-snmprec', ['device' => (string) $device->device_id, '--model' => 'wrt54g'])
        ->expectsOutput('The device is detected as generic. Add detection for its OS first, then collect the test data. To get a full walk to work from, use --full --output=<file>.')
        ->assertExitCode(1);
});

test('full walk requires output', function (): void {
    $this->artisan('dev:collect-snmprec', ['device' => 'unused', '--full' => true])
        ->expectsOutput('--full requires --output, because a full walk is not saved as a test fixture.')
        ->assertExitCode(1);
});

test('full walk rejects modules', function (): void {
    $this->artisan('dev:collect-snmprec', [
        'device' => 'unused',
        '--model' => 'test',
        '--full' => true,
        '--modules' => 'ports',
    ])->expectsOutput('--full and --modules cannot be used together because a full walk does not run modules.')
        ->assertExitCode(1);
});

test('completion', function (): void {
    $device = Device::factory()->create([
        'hostname' => 'router.example.com',
    ]);

    $cmd = new DevCollectSnmprec;

    $completionsByHost = $cmd->completeArgument('device', 'router.');
    expect($completionsByHost)->toContain('router.example.com');

    $completionsById = $cmd->completeArgument('device', (string) $device->device_id);
    expect($completionsById)->toContain('router.example.com');

    $modulesOption = $cmd->getDefinition()->getOption('modules');
    expect($modulesOption)->toBeInstanceOf(DynamicInputOption::class);
    $moduleCompletions = $cmd->completeOptionValue($modulesOption, 'ports,sen');
    expect($moduleCompletions)->toContain('ports,sensors');

    $modelOption = $cmd->getDefinition()->getOption('model');
    expect($modelOption)->toBeInstanceOf(DynamicInputOption::class);
    $modelCompletions = $cmd->completeOptionValue($modelOption, 'wi');
    expect($modelCompletions)->toContain('wifi');
});