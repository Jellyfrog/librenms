<?php

use App\Data\Graphing\GraphImage;
use App\Data\Graphing\GraphParameters;
use App\Graphs\AbstractGraph;
use LibreNMS\Enum\ImageFormat;
use LibreNMS\Exceptions\GraphException;
use LibreNMS\Exceptions\NoGraphData;

uses(LibreNMS\Tests\TestCase::class);

test('errors from drawing become graph errors', function (): void {
    $graph = makeGraph(fn () => throw new RuntimeException('jpgraph failed'));

    try {
        $graph->render();
        $this->fail('Expected a graph exception');
    } catch (GraphException $e) {
        expect($e->getMessage())->toBe('Graph Error');
        expect($e->getPrevious())->toBeInstanceOf(RuntimeException::class);
    }
});

test('graph errors are kept', function (): void {
    $this->expectException(NoGraphData::class);

    makeGraph(fn () => throw new NoGraphData)->render();
});

test('image is named after the user title', function (): void {
    expect(makeGraph(params: ['graph_title' => 'Core uplink'])->render()->title)->toBe('Core uplink');
    expect(makeGraph()->render()->title)->toBe('Drawn');
});

test('drawing errors preserve the enabled title', function (): void {
    $error = new NoGraphData;
    $graph = makeGraph(fn () => throw $error, ['title' => 'yes']);

    try {
        $graph->render();
        $this->fail('Expected a graph exception');
    } catch (NoGraphData $e) {
        expect($e)->toBe($error);
        expect($e->graphTitle)->toBe('Device::Test');
    }
});

test('unauthorized graphs do not resolve the title', function (): void {
    $graph = makeGraph(params: ['title' => 'yes'], isAuthorized: fn () => false);

    try {
        $graph->render();
        $this->fail('Expected a graph exception');
    } catch (GraphException $e) {
        expect($e->graphTitle)->toBeNull();
    }
});

test('authorization is checked once', function (): void {
    $checks = 0;
    $graph = makeGraph(isAuthorized: function () use (&$checks) {
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
function makeGraph(?\Closure $draw = null, array $params = [], ?\Closure $isAuthorized = null): AbstractGraph
{
    $draw ??= fn () => new GraphImage(ImageFormat::Png, 'Drawn', 'image-data');
    $isAuthorized ??= fn () => true;

    return new class(new GraphParameters(['type' => 'device_test', ...$params]), $draw, $isAuthorized) extends AbstractGraph
    {
        public function __construct(GraphParameters $params, private readonly \Closure $drawer, private readonly \Closure $authorizer)
        {
            parent::__construct($params);
        }

        protected function isAuthorized(): bool
        {
            return ($this->authorizer)();
        }

        protected function draw(): GraphImage
        {
            return ($this->drawer)();
        }
    };
}
