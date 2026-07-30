<?php

use App\Http\Parsers\AlertLogDetailParser;

uses(LibreNMS\Tests\TestCase::class);

beforeEach(function (): void {
    $this->parser = new AlertLogDetailParser();
});

test('format basic rule alert', function (): void {
    $details = [
        'rule' => [
            ['message' => 'Test alert', 'value' => 10],
        ],
    ];

    $output = $this->parser->parse($details);

    expect($output['sections'][0]['items'][0]['row'])->toEqual(0);
    expect($output['sections'][0]['items'][0]['fields'][0]['label'])->toEqual('message');
    expect($output['sections'][0]['items'][0]['fields'][0]['value'])->toEqual('Test alert');
    expect($output['sections'][0]['items'][0]['fields'][1]['label'])->toEqual('value');
    expect($output['sections'][0]['items'][0]['fields'][1]['value'])->toEqual('10');
});

test('format alert with diff', function (): void {
    $details = [
        'diff' => [
            'added' => [
                ['message' => 'New item'],
            ],
            'resolved' => [
                ['message' => 'Fixed item'],
            ],
        ],
        'rule' => [
            ['message' => 'Still alert'],
        ],
    ];

    $output = $this->parser->parse($details);

    expect($output['sections'][0]['title'])->toEqual('Modifications');
    expect($output['sections'][0]['items'][0]['type'])->toEqual('added');
    expect($output['sections'][0]['items'][0]['fields'][0]['value'])->toEqual('New item');
    expect($output['sections'][0]['items'][1]['type'])->toEqual('resolved');
    expect($output['sections'][0]['items'][1]['fields'][0]['value'])->toEqual('Fixed item');

    expect($output['sections'][1]['title'])->toEqual('All current items');
    expect($output['sections'][1]['items'][0]['fields'][0]['value'])->toEqual('Still alert');
});

test('format bill', function (): void {
    $details = [
        'rule' => [
            [
                'bill_id' => 123,
                'bill_name' => 'Test Bill',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Bill');
    expect($fields[0]['value'])->toEqual('Test Bill');
    $this->assertStringContainsString('bill_id=123', $fields[0]['url']);
});

test('format port', function (): void {
    $details = [
        'rule' => [
            [
                'port_id' => 1,
                'device_id' => 1,
                'ifDescr' => 'eth0',
                'ifAlias' => 'WAN Interface',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Port');
    expect($fields[0]['value'])->toEqual('eth0');
    expect($fields[1]['label'])->toEqual('Alias');
    expect($fields[1]['value'])->toEqual('WAN Interface');
});

test('format sensor', function (): void {
    $details = [
        'rule' => [
            [
                'device_id' => 1,
                'sensor_id' => 456,
                'sensor_class' => 'temperature',
                'sensor_current' => 35,
                'sensor_limit' => 40,
                'sensor_limit_warn' => 38,
                'sensor_descr' => 'CPU Temp',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Sensor');
    expect($fields[0]['value'])->toEqual('CPU Temp');
    expect($fields[1]['label'])->toEqual('Value');
    $this->assertStringContainsString('35', $fields[1]['value']);
    $this->assertStringContainsString('(temperature)', $fields[1]['value']);
    expect($fields[2]['label'])->toEqual('Thresholds');
    $this->assertStringContainsString('High Warn: 38', $fields[2]['value']);
    $this->assertStringContainsString('High: 40', $fields[2]['value']);
});

test('format sensor state', function (): void {
    $details = [
        'rule' => [
            [
                'device_id' => 1,
                'sensor_id' => 789,
                'sensor_class' => 'state',
                'sensor_current' => 2,
                'state_descr' => 'Critical',
                'state_value' => 2, // Used by StateTranslation if constructor receives it
                'sensor_descr' => 'Power State',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Sensor');
    expect($fields[0]['value'])->toEqual('Power State');
    expect($fields[1]['label'])->toEqual('State');
    $this->assertStringContainsString('Critical (numerical: 2)', $fields[1]['value']);
});

test('format access point', function (): void {
    $details = [
        'rule' => [
            [
                'accesspoint_id' => 101,
                'device_id' => 1,
                'name' => 'AP-01',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Access Point');
    expect($fields[0]['value'])->toEqual('AP-01');
    $this->assertStringContainsString('accesspoints', $fields[0]['url']);
    $this->assertStringContainsString('ap=101', $fields[0]['url']);
});

test('format service', function (): void {
    $details = [
        'rule' => [
            [
                'service_id' => 202,
                'device_id' => 1,
                'service_name' => 'HTTP',
                'service_type' => 'http',
                'service_ip' => '1.2.3.4',
                'service_desc' => 'Web Service',
                'service_message' => 'Connection refused',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Service');
    expect($fields[0]['value'])->toEqual('HTTP (http)');
    $this->assertStringContainsString('services', $fields[0]['url']);
    $this->assertStringContainsString('view=detail', $fields[0]['url']);
    expect($fields[1]['label'])->toEqual('Host');
    expect($fields[1]['value'])->toEqual('1.2.3.4');
    expect($fields[2]['label'])->toEqual('Description');
    expect($fields[2]['value'])->toEqual('Web Service');
    expect($fields[3]['label'])->toEqual('Message');
    expect($fields[3]['value'])->toEqual('Connection refused');
});

test('format bgp peer', function (): void {
    $details = [
        'rule' => [
            [
                'bgpPeer_id' => 303,
                'device_id' => 1,
                'bgpPeerIdentifier' => '10.0.0.1',
                'bgpPeerDescr' => 'ISP-A',
                'bgpPeerRemoteAs' => 65001,
                'bgpPeerState' => 'idle',
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('BGP Peer');
    expect($fields[0]['value'])->toEqual('10.0.0.1');
    $this->assertStringContainsString('routing', $fields[0]['url']);
    $this->assertStringContainsString('proto=bgp', $fields[0]['url']);
    expect($fields[1]['label'])->toEqual('Description');
    expect($fields[1]['value'])->toEqual('ISP-A');
    expect($fields[2]['label'])->toEqual('Remote AS');
    expect($fields[2]['value'])->toEqual('65001');
    expect($fields[3]['label'])->toEqual('State');
    expect($fields[3]['value'])->toEqual('idle');
});

test('format mempool', function (): void {
    $details = [
        'rule' => [
            [
                'mempool_id' => 404,
                'mempool_descr' => 'System RAM',
                'mempool_perc' => 85.5,
                'mempool_free' => 1024 * 1024 * 100, // 100MB
                'mempool_total' => 1024 * 1024 * 1024, // 1GB
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Memory Pool');
    expect($fields[0]['value'])->toEqual('System RAM');
    $this->assertStringContainsString('mempool_usage', $fields[0]['url']);
    $this->assertStringContainsString('404', $fields[0]['url']);
    expect($fields[1]['label'])->toEqual('Usage');
    $this->assertStringContainsString('Usage: 85.5', $fields[1]['value']);
    $this->assertStringContainsString('Free: 104.86 MB', $fields[1]['value']);
    $this->assertStringContainsString('Total: 1.07 GB', $fields[1]['value']);
});

test('format application', function (): void {
    $details = [
        'rule' => [
            [
                'app_id' => 505,
                'device_id' => 1,
                'app_type' => 'nginx',
                'app_status' => 'up',
                'metric' => 'requests',
                'value' => 5000,
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    expect($fields[0]['label'])->toEqual('Application');
    expect($fields[0]['value'])->toEqual('nginx');
    $this->assertStringContainsString('apps', $fields[0]['url']);
    $this->assertStringContainsString('app=nginx', $fields[0]['url']);
    expect($fields[1]['label'])->toEqual('Status');
    expect($fields[1]['value'])->toEqual('up');
    expect($fields[2]['label'])->toEqual('Metric');
    expect($fields[2]['value'])->toEqual('requests = 5000');
});

test('fallback formatting', function (): void {
    $details = [
        'rule' => [
            [
                'custom_key' => 'custom_value',
                'device_id' => 1, // should be skipped
                'some_id' => 123, // should be skipped (contains id)
                'description' => 'test', // should be skipped (contains desc)
                'another_val' => 'present',
                'sysContact' => 'admin@example.com', // should be included
                'community' => 'public', // should be skipped
                'snmpver' => 'v2c', // should be skipped
                'authname' => 'user', // should be skipped (contains auth)
                'cryptopass' => 'secret', // should be skipped (contains pass)
            ],
        ],
    ];

    $output = $this->parser->parse($details);
    $fields = $output['sections'][0]['items'][0]['fields'];

    $labels = array_column($fields, 'label');
    expect($labels)->toContain('custom_key');
    expect($labels)->toContain('another_val');
    expect($labels)->toContain('sysContact');
    expect($labels)->not->toContain('device_id');
    expect($labels)->not->toContain('some_id');
    expect($labels)->not->toContain('description');
    expect($labels)->not->toContain('community');
    expect($labels)->not->toContain('snmpver');
    expect($labels)->not->toContain('authname');
    expect($labels)->not->toContain('cryptopass');
});
