<?php

use App\Facades\LibrenmsConfig;
use LibreNMS\ValidationResult;
use LibreNMS\Validations\Rrd\CheckRrdcachedConnectivity;

uses(LibreNMS\Tests\TestCase::class);

/**
 * @return array<string, array{string, string}>
 */
dataset('unreachable', function () {
    return [
        'unix prefix' => ['unix:/nonexistent/rrdcached.sock', '/nonexistent/rrdcached.sock does not exist'],
        'bare unix path' => ['/nonexistent/rrdcached.sock', '/nonexistent/rrdcached.sock does not exist'],
        'host and port' => ['127.0.0.1:1', 'server 127.0.0.1 on port 1'],
        'bracketed ipv6 and port' => ['[::1]:1', 'server ::1 on port 1'],
    ];
});

test('unreachable', function (string $address, string $message): void {
    LibrenmsConfig::set('rrdcached', $address);

    $result = (new CheckRrdcachedConnectivity)->validate();

    expect($result->getStatus())->toBe(ValidationResult::FAILURE);
    $this->assertStringContainsString($message, $result->getMessage());
})->with('unreachable');

test('connects', function (): void {
    $server = stream_socket_server('tcp://127.0.0.1:0');
    LibrenmsConfig::set('rrdcached', (string) stream_socket_get_name($server, false));

    expect((new CheckRrdcachedConnectivity)->validate()->getStatus())->toBe(ValidationResult::SUCCESS);
    fclose($server);
});
