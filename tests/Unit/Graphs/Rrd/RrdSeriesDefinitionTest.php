<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\DataSeries;
use App\Data\Graphing\GraphOptions;
use App\Data\Graphing\GraphParameters;
use App\Data\Graphing\SeriesStyle;
use App\Data\TimeSeries\MetricIdentity;
use App\Facades\LibrenmsConfig;
use App\Graphs\Rrd\RrdFileResolver;
use App\Graphs\Rrd\RrdSeriesDefinition;
use LibreNMS\Exceptions\NoGraphData;

beforeEach(function () {
    $this->metric = new MetricIdentity('test-metric', ['hostname' => 'localhost']);
    $this->rrdFiles = Mockery::mock(RrdFileResolver::class);
    $this->rrdFiles->shouldReceive('pathFor')->with($this->metric)->andReturn('host/test.rrd')->byDefault();
    LibrenmsConfig::shouldReceive('get')->byDefault()->andReturn(null);
    LibrenmsConfig::shouldReceive('has')->byDefault()->andReturn(true);
    LibrenmsConfig::shouldReceive('get')->with(Mockery::pattern('/^graph_colours\./'))->byDefault()->andReturn('FF0000');
});

test('it draws lines with fill and invert', function () {
    expect(build([
        'in' => new DataSeries($this->metric, 'in', 'In', 'bps', style: new SeriesStyle(fill: true)),
        'out' => new DataSeries($this->metric, 'out', 'Out', 'bps', style: new SeriesStyle(color: 'AA0000', fill: true, fillColor: 'BB0000', invert: true)),
    ], new GraphOptions(legendTitle: 'Traffic')))->toBe([
        "COMMENT:Traffic                    Now        Min        Max        Avg\l",
        'DEF:ds0=host/test.rrd:in:AVERAGE',
        'DEF:ds0min=host/test.rrd:in:MIN',
        'DEF:ds0max=host/test.rrd:in:MAX',
        'DEF:ds1=host/test.rrd:out:AVERAGE',
        'DEF:ds1min=host/test.rrd:out:MIN',
        'DEF:ds1max=host/test.rrd:out:MAX',
        'CDEF:ds1drawn=ds1,-1,*',
        'AREA:ds0#FF000020',
        'AREA:ds1drawn#BB0000',
        'LINE1.25:ds0#FF0000:In               ',
        'GPRINT:ds0:LAST:%5.2lf%sbps',
        'GPRINT:ds0min:MIN:%5.2lf%sbps',
        'GPRINT:ds0max:MAX:%5.2lf%sbps',
        'GPRINT:ds0:AVERAGE:%5.2lf%sbps\n',
        'LINE1.25:ds1drawn#AA0000:Out              ',
        'GPRINT:ds1:LAST:%5.2lf%sbps',
        'GPRINT:ds1min:MIN:%5.2lf%sbps',
        'GPRINT:ds1max:MAX:%5.2lf%sbps',
        'GPRINT:ds1:AVERAGE:%5.2lf%sbps\n',
        'HRULE:0#555555',
    ]);
});

test('max fills are drawn behind the fills', function () {
    $definition = build([
        'in' => new DataSeries($this->metric, 'in', 'In', scale: 8, style: new SeriesStyle(color: '608720', fill: true, fillColor: '90B040', maxFillColor: 'D7FFC7')),
        'out' => new DataSeries($this->metric, 'out', 'Out', style: new SeriesStyle(color: '606090', maxFillColor: 'E0E0FF', invert: true)),
    ]);

    expect($definition)->toContain('CDEF:ds1maxdrawn=ds1max,-1,*');
    $drawing = array_values(preg_grep('/^(AREA|LINE)/', $definition));
    expect($drawing)->toBe([
        'AREA:ds0max#D7FFC7',
        'AREA:ds1maxdrawn#E0E0FF',
        'AREA:ds0#90B040',
        'LINE1.25:ds0#608720:In               ',
        'LINE1.25:ds1drawn#606090:Out              ',
    ]);
});

test('draw scale only changes the drawing', function () {
    expect(build([
        'a' => new DataSeries($this->metric, 'usage', 'A', style: new SeriesStyle(stacked: true, drawScale: 0.5)),
        'b' => new DataSeries($this->metric, 'usage', 'B', style: new SeriesStyle(stacked: true, drawScale: 0.5)),
    ], new GraphOptions(legendTitle: 'Load %')))->toBe([
        "COMMENT:Load %                  Now     Min     Max     Avg\l",
        'DEF:ds0=host/test.rrd:usage:AVERAGE',
        'DEF:ds0min=host/test.rrd:usage:MIN',
        'DEF:ds0max=host/test.rrd:usage:MAX',
        'CDEF:ds0drawn=ds0,0.5,*',
        'DEF:ds1=host/test.rrd:usage:AVERAGE',
        'DEF:ds1min=host/test.rrd:usage:MIN',
        'DEF:ds1max=host/test.rrd:usage:MAX',
        'CDEF:ds1drawn=ds1,0.5,*',
        'AREA:ds0drawn#FF0000:A                ',
        'GPRINT:ds0:LAST:%5.2lf%s',
        'GPRINT:ds0min:MIN:%5.2lf%s',
        'GPRINT:ds0max:MAX:%5.2lf%s',
        'GPRINT:ds0:AVERAGE:%5.2lf%s\n',
        'AREA:ds1drawn#FF0000:B                :STACK',
        'GPRINT:ds1:LAST:%5.2lf%s',
        'GPRINT:ds1min:MIN:%5.2lf%s',
        'GPRINT:ds1max:MAX:%5.2lf%s',
        'GPRINT:ds1:AVERAGE:%5.2lf%s\n',
    ]);
});

test('series scale changes the value everywhere', function () {
    expect(build(['a' => new DataSeries($this->metric, 'bytes', 'Bits', scale: 8)]))->toBe([
        "COMMENT:                        Now     Min     Max     Avg\l",
        'DEF:ds0raw=host/test.rrd:bytes:AVERAGE',
        'CDEF:ds0=ds0raw,8,*',
        'DEF:ds0rawmin=host/test.rrd:bytes:MIN',
        'CDEF:ds0min=ds0rawmin,8,*',
        'DEF:ds0rawmax=host/test.rrd:bytes:MAX',
        'CDEF:ds0max=ds0rawmax,8,*',
        'LINE1.25:ds0#FF0000:Bits             ',
        'GPRINT:ds0:LAST:%5.2lf%s',
        'GPRINT:ds0min:MIN:%5.2lf%s',
        'GPRINT:ds0max:MAX:%5.2lf%s',
        'GPRINT:ds0:AVERAGE:%5.2lf%s\n',
    ]);
});

test('it adds the previous period of stacked series', function () {
    $definition = build([
        'a' => new DataSeries($this->metric, 'a', 'A', style: new SeriesStyle(stacked: true)),
        'b' => new DataSeries($this->metric, 'b', 'B', scale: 8, style: new SeriesStyle(stacked: true, drawScale: 0.5)),
    ], params: ['from' => 100, 'to' => 200, 'previous' => 'yes']);

    expect($definition)->toContain('DEF:ds0prev=host/test.rrd:a:AVERAGE:start=0:end=100');
    expect($definition)->toContain('SHIFT:ds1prev:100');
    expect($definition)->toContain('CDEF:previous=ds0prev,UN,0,ds0prev,IF,ds1prev,UN,0,ds1prev,IF,4,*,+');
    expect($definition)->toContain('AREA:previous#99999999:');
});

test('lines do not draw the previous period', function () {
    $definition = build(['a' => new DataSeries($this->metric, 'a', 'A')], params: ['from' => 100, 'to' => 200, 'previous' => 'yes']);

    expect(preg_grep('/previous|prev/', $definition))->toBeEmpty();
});

test('it uses the palette of the options', function () {
    LibrenmsConfig::shouldReceive('get')->with('graph_colours.oranges.0')->andReturn('EE7700');

    $definition = build(['a' => new DataSeries($this->metric, 'a', 'A')], new GraphOptions(palette: 'oranges'));

    expect($definition)->toContain('LINE1.25:ds0#EE7700:A                ');
});

test('it escapes units in value formats and widens the columns', function () {
    $definition = build(['a' => new DataSeries($this->metric, 'a', 'A', 'a%:b')]);

    expect($definition)->toContain('GPRINT:ds0:LAST:%5.2lf%sa%%\:b');
    expect($definition[0])->toBe("COMMENT:                            Now         Min         Max         Avg\l");
});

test('it skips series without an rrd file', function () {
    $missing = new MetricIdentity('missing', ['hostname' => 'localhost']);
    $this->rrdFiles->shouldReceive('pathFor')->with($missing)->andReturn(null);

    $definition = build([
        'gone' => new DataSeries($missing, 'a', 'Gone'),
        'here' => new DataSeries($this->metric, 'b', 'Here'),
    ]);

    expect($definition)->toContain('DEF:ds0=host/test.rrd:b:AVERAGE');
    expect(preg_grep('/Gone/', $definition))->toBeEmpty();
});

test('stack average divides by the series with rrd files', function () {
    $missing = new MetricIdentity('test-metric', ['hostname' => 'localhost', 'index' => 3]);
    $this->rrdFiles->shouldReceive('pathFor')->with($missing)->andReturn(null);
    $style = new SeriesStyle(stacked: true, stackAverage: true);

    $definition = build([
        'a' => new DataSeries($this->metric, 'usage', 'A', style: $style),
        'b' => new DataSeries($this->metric, 'usage', 'B', style: $style),
        'c' => new DataSeries($missing, 'usage', 'C', style: $style),
    ]);

    expect($definition)->toContain('CDEF:ds0drawn=ds0,0.5,*');
    expect($definition)->toContain('CDEF:ds1drawn=ds1,0.5,*');
    expect(preg_grep('/^DEF:ds2/', $definition))->toBeEmpty();
});

test('it reports no data when no series has an rrd file', function () {
    $this->rrdFiles->shouldReceive('pathFor')->with($this->metric)->andReturn(null);

    $this->expectException(NoGraphData::class);
    $this->expectExceptionMessage('No Data files');

    build(['a' => new DataSeries($this->metric, 'a', 'A')]);
});

/**
 * @param  array<string, DataSeries>  $series
 * @param  array<string, mixed>  $params
 * @return array<int, string>
 */
function build(array $series, GraphOptions $options = new GraphOptions, array $params = ['from' => 100, 'to' => 200]): array
{
    return build(new GraphParameters($params));
}