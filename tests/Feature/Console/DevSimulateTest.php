<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Console\Commands\DevSimulate;
use App\Models\Device;
use Illuminate\Console\OutputStyle;
use LibreNMS\Util\Snmpsim;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('added device can be polled with the current community and port', function () {
    addDevice('test-community', new Snmpsim(port: 1161));
    $config = Device::where('hostname', 'snmpsim-test-community')->firstOrFail()->polling()->snmp();
    expect($config->community)->toBe('test-community');
    expect($config->version)->toBe('v2c');
    expect($config->port)->toBe(1161);

    // running again with another port updates the same device
    addDevice('test-community', new Snmpsim(port: 1162));
    expect(Device::where('hostname', 'snmpsim-test-community')->count())->toBe(1);
    $config = Device::where('hostname', 'snmpsim-test-community')->firstOrFail()->polling()->snmp();
    expect($config->community)->toBe('test-community');
    expect($config->port)->toBe(1162);
});

/**
 * Add the device like dev:simulate does after snmpsim started, without starting snmpsim.
 */
function addDevice(string $community, Snmpsim $snmpsim): void
{
    $command = new DevSimulate;
    $command->setLaravel($this->app);
    $input = new ArrayInput([], $command->getDefinition());
    $command->setInput($input);
    $command->setOutput(new OutputStyle($input, new BufferedOutput));

    new \ReflectionProperty($command, 'snmpsim')->setValue($command, $snmpsim);

    new \ReflectionMethod($command, 'addDevice')->invoke($command, $community);
}