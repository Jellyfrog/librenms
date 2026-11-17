<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('serves routes and route function as javascript', function () {
    $response = $this->actingAs(User::factory()->create(['enabled' => 1]))->get(route('js.routes'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->assertHeader('ETag');
    expect($response->getContent())->toStartWith('globalThis.Ziggy={');
    $this->assertStringContainsString('"dashboard.widget.add":', $response->getContent());
    $this->assertStringContainsString('.route=', $response->getContent());

    $cacheControl = $response->headers->get('Cache-Control');
    $this->assertStringContainsString('private', $cacheControl);
    $this->assertStringContainsString('must-revalidate', $cacheControl);
    $this->assertStringContainsString('max-age=0', $cacheControl);
});

test('returns not modified when etag matches', function () {
    $this->actingAs(User::factory()->create(['enabled' => 1]));
    $etag = $this->get(route('js.routes'))->headers->get('ETag');

    $this->get(route('js.routes'), ['If-None-Match' => $etag])
        ->assertStatus(304)
        ->assertContent('');
});

test('requires authentication', function () {
    $this->get(route('js.routes'))
        ->assertRedirect(route('login'));
});

test('guest layout does not load routes script', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee(route('js.routes'), false);
});

test('pending two factor user can load routes script', function () {
    LibrenmsConfig::set('twofactor', true);
    $twoFactorPending = ['twofactoradd' => ['key' => 'AAAAAAAAAAAAAAAA', 'counter' => false, 'fails' => 0]];

    $this->actingAs(User::factory()->create(['enabled' => 1]))
        ->withSession($twoFactorPending)
        ->get(route('2fa.form'))
        ->assertOk()
        ->assertSee('<script src="' . route('js.routes') . '"></script>', false);

    $this->get(route('js.routes'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');

    $this->get(route('about'))
        ->assertRedirect('/2fa');
});