<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\User;
use LibreNMS\Util\Dns;
use Mockery\MockInterface;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('unauthenticated user cannot preview template', function (): void {
    $response = $this->postJson(route('ajax.template.preview'), [
        'template' => '{{ hostname }}',
    ]);

    $response->assertUnauthorized();
});

test('authenticated user can preview template', function (): void {
    $user = User::factory()->create(['enabled' => 1]);

    $response = $this->actingAs($user)->postJson(route('ajax.template.preview'), [
        'template' => '{{ hostname|lower }} ({{ ip }})',
        'variables' => [
            'hostname' => 'SWITCH01.EXAMPLE.COM',
            'ip' => '10.0.0.1',
        ],
    ]);

    $response->assertOk();
    $response->assertJson([
        'preview' => 'switch01.example.com (10.0.0.1)',
    ]);
});

test('preview handles missing variables', function (): void {
    $user = User::factory()->create(['enabled' => 1]);

    $response = $this->actingAs($user)->postJson(route('ajax.template.preview'), [
        'template' => '{{ hostname }} - {{ missing }}',
        'variables' => [
            'hostname' => 'switch01',
        ],
    ]);

    $response->assertOk();
    $response->assertJson([
        'preview' => 'switch01 - ',
    ]);
});

test('resolves ip from hostname', function (): void {
    $this->mock(Dns::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getAddresses')->with('switch01.example.com')->once()->andReturn(['10.0.0.1']);
    });

    $response = $this->actingAs(User::factory()->admin()->create(['enabled' => 1]))->postJson(route('ajax.template.preview'), [
        'template' => '{{ hostname }} ({{ ip }})',
        'variables' => ['hostname' => 'switch01.example.com', 'ip' => 'unknown'],
        'resolve_ip' => 'switch01.example.com',
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'preview' => 'switch01.example.com (10.0.0.1)',
        'resolved_ip' => '10.0.0.1',
    ]);
});

test('unresolvable hostname keeps ip variable', function (): void {
    $this->mock(Dns::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getAddresses')->once()->andReturn([]);
    });

    $response = $this->actingAs(User::factory()->admin()->create(['enabled' => 1]))->postJson(route('ajax.template.preview'), [
        'template' => '{{ ip }}',
        'variables' => ['ip' => 'unknown'],
        'resolve_ip' => 'does-not-exist.invalid',
    ]);

    $response->assertOk();
    $response->assertExactJson(['preview' => 'unknown', 'resolved_ip' => null]);
});

test('user who cannot add devices does not resolve ip', function (): void {
    $this->mock(Dns::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('getAddresses');
    });

    $response = $this->actingAs(User::factory()->create(['enabled' => 1]))->postJson(route('ajax.template.preview'), [
        'template' => '{{ ip }}',
        'variables' => ['ip' => 'unknown'],
        'resolve_ip' => 'switch01.example.com',
    ]);

    $response->assertOk();
    $response->assertExactJson(['preview' => 'unknown', 'resolved_ip' => null]);
});

test('failed lookup is cached', function (): void {
    $this->mock(Dns::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getAddresses')->once()->andReturn([]);
    });
    $user = User::factory()->admin()->create(['enabled' => 1]);

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($user)->postJson(route('ajax.template.preview'), ['resolve_ip' => 'slow-dns.example.com'])
            ->assertOk()
            ->assertJson(['resolved_ip' => null]);
    }
});