<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphPageContext;
use App\Data\Graphing\GraphParameters;
use App\Data\Graphing\Legacy\LegacyAuthResult;
use App\Facades\Rrd;
use App\Graphs\Legacy\LegacyGraphScript;
use LibreNMS\Enum\ImageFormat;
use LibreNMS\Exceptions\GraphException;
use LibreNMS\Exceptions\NoGraphData;
use LibreNMS\Util\Graph;


test('dark graphs use white for the no data message and title', function () {
    $params = new GraphParameters(['graph_type' => 'svg', 'style' => 'dark', 'graph_title' => 'Traffic']);

    $image = Graph::errorImage($params, new NoGraphData);

    expect(substr_count($image->data, 'color:rgb(255, 255, 255)'))->toBe(2);
});

test('error graphs follow the session theme unless a style is requested', function () {
    session(['applied_site_style' => 'dark']);

    $dark = Graph::errorImage(new GraphParameters(['graph_type' => 'svg']), new NoGraphData);
    $light = Graph::errorImage(new GraphParameters(['graph_type' => 'svg', 'style' => 'light']), new NoGraphData);

    $this->assertStringContainsString('color:rgb(255, 255, 255)', $dark->data);
    $this->assertStringContainsString('color:rgb(0, 0, 0)', $light->data);
});

test('dark png error graphs use white text', function () {
    $params = new GraphParameters(['graph_type' => 'png', 'style' => 'dark', 'graph_title' => 'Traffic']);

    $image = imagecreatefromstring(Graph::errorImage($params, new NoGraphData)->data);
    $opaqueColors = [];
    for ($index = 0; $index < imagecolorstotal($image); $index++) {
        $color = imagecolorsforindex($image, $index);
        if ($color['alpha'] === 0) {
            $opaqueColors[] = $color;
        }
    }

    expect($opaqueColors)->not->toBeEmpty();
    foreach ($opaqueColors as $color) {
        expect($color)->toBe(['red' => 255, 'green' => 255, 'blue' => 255, 'alpha' => 0]);
    }
    imagedestroy($image);
});

test('legacy script errors preserve the title set before failure', function () {
    Rrd::partialMock()->shouldReceive('checkRrdExists')->once()->andReturn(false);
    $params = new GraphParameters(['type' => 'application_ntp-server_stats', 'title' => 'yes']);
    $auth = new LegacyAuthResult(true, new GraphPageContext, [
        'graph_title' => 'Router NTP',
        'device' => ['hostname' => 'router'],
        'app' => (object) ['app_id' => 1],
    ]);

    try {
        LegacyGraphScript::find($params->type, $params->subtype)->runGraph($auth, $params);
        $this->fail('Expected a graph exception');
    } catch (GraphException $e) {
        expect($e->graphTitle)->toBe('Router NTP');
    }
});

test('svg error preserves the enabled graph title', function () {
    $error = new NoGraphData('No Data files');
    $error->graphTitle = 'Router::Traffic';
    $params = new GraphParameters(['graph_type' => 'svg', 'title' => 'yes']);

    $image = Graph::errorImage($params, $error);

    expect($image->title)->toBe('Router::Traffic');
    $this->assertStringContainsString('Router::Traffic', $image->data);
    $this->assertStringContainsString('No Data files', $image->data);
    $this->assertStringContainsString('color:rgb(0, 0, 0)', $image->data);
});

test('error omits the disabled graph title', function () {
    $error = new NoGraphData;
    $error->graphTitle = 'Router::Traffic';
    $params = new GraphParameters(['graph_type' => 'svg', 'title' => 'no']);

    $image = Graph::errorImage($params, $error);

    expect($image->title)->toBe('Error');
    $this->assertStringNotContainsString('Router::Traffic', $image->data);
    $this->assertStringContainsString('No Data', $image->data);
});

test('custom error title overrides the original and is escaped', function () {
    $error = new NoGraphData;
    $error->graphTitle = 'Original title';
    $params = new GraphParameters(['graph_type' => 'svg', 'title' => 'no', 'graph_title' => '<script> & uplink']);

    $image = Graph::errorImage($params, $error);

    expect($image->title)->toBe('<script> & uplink');
    $this->assertStringContainsString('&lt;script&gt; &amp; uplink', $image->data);
    $this->assertStringNotContainsString('<script>', $image->data);
    $this->assertStringNotContainsString('Original title', $image->data);
});

test('error without an available title still renders', function () {
    $params = new GraphParameters(['graph_type' => 'svg', 'title' => 'yes']);

    $image = Graph::errorImage($params, new NoGraphData);

    expect($image->title)->toBe('Error');
    $this->assertStringContainsString('No Data', $image->data);
});

test('png draws the title above the error message', function () {
    $data = Graph::error('No Data', null, 400, 150, format: ImageFormat::Png, title: 'Router::Traffic');

    $image = imagecreatefromstring($data);
    $titlePixels = 0;
    for ($y = 5; $y < 20; $y++) {
        for ($x = 0; $x < 400; $x++) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            $titlePixels += $color['alpha'] === 0 && $color['red'] === 0 && $color['green'] === 0 && $color['blue'] === 0 ? 1 : 0;
        }
    }

    expect($titlePixels)->toBeGreaterThan(0);
    expect(imagesx($image))->toBe(400);
    expect(imagesy($image))->toBe(150);
    imagedestroy($image);
});
