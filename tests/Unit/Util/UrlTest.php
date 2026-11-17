<?php

use LibreNMS\Util\Url;

uses(LibreNMS\Tests\TestCase::class);

test('generate', function (): void {
    expect(Url::generate(['page' => 'device', 'device' => 1], ['tab' => 'loadbalancer']))->toBe(url('device') . '/device=1/tab=loadbalancer/');
    expect(Url::generate(['page' => 'plugin/v1', 'opt' => 'x']))->toBe(url('plugin/v1') . '/');
});

test('generate encodes page', function (): void {
    $url = Url::generate(['page' => "x';alert(1);//\"><img>", 'device' => 1]);

    $this->assertStringNotContainsString("'", $url);
    $this->assertStringNotContainsString('"', $url);
    $this->assertStringNotContainsString('<', $url);
    expect($url)->toBe(url('x%27%3Balert%281%29%3B//%22%3E%3Cimg%3E') . '/device=1/');
});
