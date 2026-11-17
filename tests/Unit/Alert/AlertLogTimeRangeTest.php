<?php

uses(\App\Http\Controllers\Table\AlertLogController::class);
use App\Http\Parsers\AlertLogDetailParser;
use \App\Http\Controllers\Table\AlertLogController;
use \LibreNMS\Tests\Unit\Alert\TestableAlertLogController;
use App\Models\AlertLog;
use Illuminate\Http\Request;

test('fixed range filters alert log time', function () {
    $controller = new TestableAlertLogController(new AlertLogDetailParser);
    expect(validationRules()['from'])->toBe('nullable|date_or_relative');
    expect(validationRules()['to'])->toBe('nullable|date_or_relative');

    $query = AlertLog::query();
    $filters = rangeFilters();
    $filters['from']($query, '2026-10-01T00:00:00Z');
    $filters['to']($query, '2026-10-02T00:00:00Z');

    $this->assertStringContainsString('alert_log.time_logged >= FROM_UNIXTIME(?)', $query->toSql());
    $this->assertStringContainsString('alert_log.time_logged <= FROM_UNIXTIME(?)', $query->toSql());
    expect($query->getBindings())->toBe([1790812800, 1790899200]);
});

test('relative range remains rolling', function () {
    $query = AlertLog::query();
    $filters = rangeFilters();
    $filters['from']($query, '-1d');

    expect($query->getBindings()[0])->toEqualWithDelta(time() - 86400, 2);
});

class TestableAlertLogController extends AlertLogController
{
    /** @return array<string, string> */
    function validationRules(): array
    {
        return $this->rules();
    }

    /** @return array<int|string, mixed> */
    function rangeFilters(): array
    {
        return $this->filterFields(new Request);
    }
}
