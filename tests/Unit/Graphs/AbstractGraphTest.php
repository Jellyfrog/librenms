<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphImage;
use \App\Graphs\AbstractGraph;
use App\Data\Graphing\GraphParameters;
use LibreNMS\Enum\ImageFormat;
use LibreNMS\Exceptions\GraphException;
use LibreNMS\Exceptions\NoGraphData;

test('errors from drawing become graph errors', function () {
    $graph = graph(fn () => throw new RuntimeException('jpgraph failed'));

    try {
        $graph->render();
        $this->fail('Expected a graph exception');
    } catch (GraphException $e) {
        expect($e->getMessage())->toBe('Graph Error');
        expect($e->getPrevious())->toBeInstanceOf(RuntimeException::class);
    }
});

test('graph errors are kept', function () {
    $this->expectException(NoGraphData::class);

    graph(fn () => throw new NoGraphData)->render();
});

test('image is named after the user title', function () {
    expect(graph(params: ['graph_title' => 'Core uplink'])->render()->title)->toBe('Core uplink');
    expect(graph()->render()->title)->toBe('Drawn');
});

test('drawing errors preserve the enabled title', function () {
    $error = new NoGraphData;
    $graph = graph(fn () => throw $error, ['title' => 'yes']);

    try {
        $graph->render();
        $this->fail('Expected a graph exception');
    } catch (NoGraphData $e) {
        expect($e)->toBe($error);
        expect($e->graphTitle)->toBe('Device::Test');
    }
});

test('unauthorized graphs do not resolve the title', function () {
    $graph = graph(params: ['title' => 'yes'], isAuthorized: fn () => false);

    try {
        $graph->render();
        $this->fail('Expected a graph exception');
    } catch (GraphException $e) {
        expect($e->graphTitle)->toBeNull();
    }
});

test('authorization is checked once', function () {
    $checks = 0;
    $graph = graph(isAuthorized: function () use (&$checks) {
        $checks++;

        return true;
    });

    $graph->authorize();
    $graph->render();

    expect($checks)->toBe(1);
});

/**
 * @param  array<string, mixed>  $params
 */
function graph(?\Closure $draw = null, array $params = [], ?\Closure $isAuthorized = null): AbstractGraph
{
    $draw ??= fn () => new GraphImage(ImageFormat::Png, 'Drawn', 'image-data');
    $isAuthorized ??= fn () => true;

    return new class(new GraphParameters(['type' => 'device_test', ...$params]), $draw, $isAuthorized) extends AbstractGraph
    {
        function __construct(GraphParameters $params, private readonly \Closure $drawer, private readonly \Closure $authorizer)
        {
            __construct($params);
        }

        function isAuthorized(): bool
        {
            return ($this->authorizer)();
        }

        function draw(): GraphImage
        {
            return ($this->drawer)();
        }
    };
}
