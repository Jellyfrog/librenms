<?php

namespace LibreNMS\Tests\Unit\Enum;

use LibreNMS\Enum\ServiceStatus;
use LibreNMS\Enum\Severity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServiceStatusTest extends TestCase
{
    public function testDatabaseValuesMatchPluginExitCodes(): void
    {
        $this->assertSame(0, ServiceStatus::Ok->value);
        $this->assertSame(1, ServiceStatus::Warning->value);
        $this->assertSame(2, ServiceStatus::Critical->value);
        $this->assertSame(3, ServiceStatus::Unknown->value);
    }

    public static function exitCodes(): array
    {
        return [
            'ok' => [0, ServiceStatus::Ok],
            'warning' => [1, ServiceStatus::Warning],
            'critical' => [2, ServiceStatus::Critical],
            'unknown' => [3, ServiceStatus::Unknown],
            'exec failed' => [-1, ServiceStatus::Unknown],
            'generic error' => [4, ServiceStatus::Unknown],
            'not executable' => [126, ServiceStatus::Unknown],
            'command not found' => [127, ServiceStatus::Unknown],
            'killed' => [137, ServiceStatus::Unknown],
            'max exit code' => [255, ServiceStatus::Unknown],
        ];
    }

    #[DataProvider('exitCodes')]
    public function testFromExitCode(int $exitCode, ServiceStatus $expected): void
    {
        $this->assertSame($expected, ServiceStatus::fromExitCode($exitCode));
    }

    public function testLabelsAndClasses(): void
    {
        $this->assertSame(['OK', 'Warning', 'Critical', 'Unknown'], array_map(fn (ServiceStatus $s) => $s->label(), ServiceStatus::cases()));
        $this->assertSame(['success', 'warning', 'danger', 'info'], array_map(fn (ServiceStatus $s) => $s->cssClass(), ServiceStatus::cases()));
        $this->assertSame('label-danger', ServiceStatus::Critical->labelClass());
        $this->assertSame('label-info', ServiceStatus::Unknown->labelClass());
    }

    public function testToSeverity(): void
    {
        $this->assertSame(Severity::Ok, ServiceStatus::Ok->toSeverity());
        $this->assertSame(Severity::Warning, ServiceStatus::Warning->toSeverity());
        $this->assertSame(Severity::Error, ServiceStatus::Critical->toSeverity());
        $this->assertSame(Severity::Unknown, ServiceStatus::Unknown->toSeverity());
    }
}
