<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphOptions;
use App\Data\Graphing\GraphParameters;
use App\Facades\LibrenmsConfig;
use App\Graphs\Rrd\RrdOptionsBuilder;

test('it autoscales without limits', function () {
    $options = scaleOptions(new GraphOptions, []);

    expect($options)->toBe(['--alt-autoscale-max', '--rigid', '--slope-mode']);
});

test('it uses the requested scale when the graph sets none', function () {
    $options = scaleOptions(new GraphOptions, ['scale_min' => '5', 'scale_max' => '50']);

    expect($options)->toBe(['-l', '5', '-u', '50', '--slope-mode']);
});

test('graph scale takes precedence over the request', function () {
    $options = scaleOptions(new GraphOptions(scaleMin: 0, scaleMax: 100), ['scale_min' => '5']);

    expect($options)->toBe(['-l', '0', '-u', '100', '--slope-mode']);
});

test('it escapes text options', function () {
    $options = scaleOptions(new GraphOptions(verticalLabel: "Bob's \"label\""), []);

    expect($options)->toContain('Bobs label');
});

test('it builds the exact image options', function () {
    LibrenmsConfig::set('mono_font', 'Mono');
    LibrenmsConfig::set('rrdgraph_def_text', '-c BACK#EEEEEE');
    LibrenmsConfig::set('rrdgraph_def_text_color', '#111111');

    $params = new GraphParameters([
        'from' => 100, 'to' => 200, 'width' => 300, 'height' => 90, 'graph_type' => 'svg', 'absolute' => 1,
        'legend' => 'no', 'graph_title' => "Bob's title", 'bg' => 'FFFFFF', 'font' => '222222', 'bbg' => '333333',
    ]);

    expect(new RrdOptionsBuilder($params)->build('ignored', new GraphOptions))->toBe([
        '--start', '100', '--end', '200', '--width', '300', '--height', '90',
        '--full-size-mode',
        '--imgformat=SVG', '-m', '0.75', '-R', 'light',
        '--font', 'TITLE:7:Mono', '--font', 'LEGEND:7:Mono', '--font', 'AXIS:6:Mono',
        '--font-render-mode', 'normal',
        '-c', 'FONT#222222', '-c', 'BACK#333333', '-c', 'CANVAS#FFFFFF',
        '--only-graph',
        '-g',
        '--title=Bobs title',
        '--alt-autoscale-max', '--rigid', '--slope-mode',
    ]);
});

test('it builds the exact graph options', function () {
    $options = new GraphOptions(
        scaleMin: 1.5, scaleRigid: true, base: 1000, unitsExponent: 3, logarithmic: true, altYGrid: true,
        verticalLabel: 'v', leftAxisFormat: 'l', rightAxis: '1:0', rightAxisLabel: 'r', unitsLength: 5,
    );

    expect(scaleOptions($options, []))->toBe([
        '-l', '1.5', '--rigid', '--slope-mode', '--alt-y-grid', '--base', '1000', '--units-exponent', '3', '--logarithmic',
        '--right-axis', '1:0', '--right-axis-label', 'r', '--left-axis-format', 'l',
        '--units-length', '5', '--vertical-label', 'v',
    ]);
});

test('it shortens long titles to fit the width', function () {
    $options = new RrdOptionsBuilder(new GraphParameters(['width' => 400, 'title' => 'yes']))->build(str_repeat('x', 100), new GraphOptions);

    expect($options)->toContain('--title=' . str_repeat('x', 65) . '...');
});

test('graph size is clamped', function () {
    $params = new GraphParameters(['width' => '200000', 'height' => [1]]);

    expect($params->width)->toBe(GraphParameters::MAX_SIZE);
    expect($params->height)->toBe(intdiv(GraphParameters::MAX_SIZE, 3));
});

/**
 * The options after the common image options, which end with the font render mode and colors
 *
 * @param  array<string, mixed>  $input
 * @return array<int, string>
 */
function scaleOptions(GraphOptions $options, array $input): array
{
    $params = new GraphParameters(['height' => 50, 'legend' => 'yes', ...$input]);
    $all = new RrdOptionsBuilder($params)->build('', $options);

    return array_slice($all, array_search('--only-graph', $all, true) + 1);
}
