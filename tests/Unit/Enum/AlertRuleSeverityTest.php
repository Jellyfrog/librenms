<?php

namespace LibreNMS\Tests\Unit\Enum;

use LibreNMS\Enum\AlertRuleSeverity;
use LibreNMS\Enum\Severity;
use LibreNMS\Tests\TestCase;

final class AlertRuleSeverityTest extends TestCase
{
    public function testRankMatchesDatabaseEnumOrder(): void
    {
        $this->assertSame(1, AlertRuleSeverity::Ok->rank());
        $this->assertSame(2, AlertRuleSeverity::Warning->rank());
        $this->assertSame(3, AlertRuleSeverity::Critical->rank());
    }

    public function testToSeverity(): void
    {
        $this->assertSame(Severity::Ok, AlertRuleSeverity::Ok->toSeverity());
        $this->assertSame(Severity::Warning, AlertRuleSeverity::Warning->toSeverity());
        $this->assertSame(Severity::Error, AlertRuleSeverity::Critical->toSeverity());
    }

    public function testSeverityFromAlertRule(): void
    {
        $this->assertSame(Severity::Ok, Severity::fromAlertRule('ok'));
        $this->assertSame(Severity::Warning, Severity::fromAlertRule('warning'));
        $this->assertSame(Severity::Error, Severity::fromAlertRule('critical'));
        $this->assertSame(Severity::Unknown, Severity::fromAlertRule(null));
        $this->assertSame(Severity::Unknown, Severity::fromAlertRule(''));
        $this->assertSame(Severity::Unknown, Severity::fromAlertRule('Critical'));
    }
}
