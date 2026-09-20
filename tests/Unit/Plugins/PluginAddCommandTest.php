<?php

namespace LibreNMS\Tests\Unit\Plugins;

use LibreNMS\Tests\TestCase;

final class PluginAddCommandTest extends TestCase
{
    public function testAPackageLibreNmsProvidesIsNotAdded(): void
    {
        // refused before composer runs, so the plugin root is never touched
        $this->artisan('plugin:add', ['package' => 'Laravel/Framework'])
            ->expectsOutput('laravel/framework is already installed by LibreNMS.')
            ->assertExitCode(1);
    }

    public function testAVersionConstraintDoesNotHideAProvidedPackage(): void
    {
        $this->artisan('plugin:add', ['package' => 'laravel/framework:^12.0'])
            ->expectsOutput('laravel/framework is already installed by LibreNMS.')
            ->assertExitCode(1);
    }
}
