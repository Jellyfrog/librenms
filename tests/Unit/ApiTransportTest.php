<?php

use App\Models\AlertTransport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http as LaravelHttp;

test('get multiline variables', function () {
    /** @var AlertTransport $transport */
    $transport = AlertTransport::factory()->api('text={{ $msg }}')->make();

    LaravelHttp::fake([
        '*' => LaravelHttp::response(),
    ]);

    $obj = ['msg' => "This is a multi-line\nalert."];
    $result = $transport->instance()->deliverAlert($obj);

    expect($result)->toBeTrue();

    LaravelHttp::assertSentCount(1);
    LaravelHttp::assertSent(fn (Request $request) => $request->method() == 'GET' &&
        $request->url() == 'https://librenms.org?text=This%20is%20a%20multi-line%0Aalert.');
});

test('post multiline variables', function () {
    /** @var AlertTransport $transport */
    $transport = AlertTransport::factory()->api(
        'text={{ $msg }}',
        'post',
        'bodytext={{ $msg }}',
    )->make();

    LaravelHttp::fake([
        '*' => LaravelHttp::response(),
    ]);

    $obj = ['msg' => "This is a post multi-line\nalert."];
    $result = $transport->instance()->deliverAlert($obj);

    expect($result)->toBeTrue();

    LaravelHttp::assertSentCount(1);
    LaravelHttp::assertSent(fn (Request $request) => $request->method() == 'POST' &&
        $request->url() == 'https://librenms.org?text=This%20is%20a%20post%20multi-line%0Aalert.' &&
        $request->body() == "bodytext=This is a post multi-line\nalert.");
});
