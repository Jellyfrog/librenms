<?php

/**
 * PluginRootTest.php
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2025 LibreNMS
 */

namespace LibreNMS\Tests\Unit\Plugins;

use App\Plugins\PluginRoot;
use LibreNMS\Tests\TestCase;

final class PluginRootTest extends TestCase
{
    public function testInstalledPackagesAreReplacedAtTheirExactVersion(): void
    {
        $replace = $this->replaceBlock();

        $this->assertSame('v12.10.1', $replace['laravel/framework']);
        $this->assertSame('1.2.0', $replace['librenms/plugin-interfaces']);
        $this->assertSame('dev-master', $replace['librenms/laravel-vue-i18n-generator']);
    }

    public function testReplacedAndProvidedVirtualsAreIncluded(): void
    {
        // illuminate/console is never installed on its own, laravel/framework replaces it,
        // and nothing installs psr/log-implementation; without these a plugin requiring
        // either pulls a second copy into the plugin vendor
        $this->assertSame('v12.10.1', $this->replaceBlock()['illuminate/console']);
        $this->assertSame('3.0.0', $this->replaceBlock()['psr/log-implementation']);
        $this->assertSame('*', $this->replaceBlock()['spatie/once']);
    }

    public function testARealInstallWinsOverAReplaceOfTheSameName(): void
    {
        $this->assertSame('v9.0.0', $this->replaceBlock()['illuminate/support']);
    }

    public function testLibreNmsDoesNotReplaceItself(): void
    {
        $this->assertArrayNotHasKey('librenms/librenms', $this->replaceBlock());
    }

    public function testTheBlockIsSortedSoItDoesNotChurn(): void
    {
        $names = array_keys($this->replaceBlock());
        $sorted = $names;
        sort($sorted);

        $this->assertSame($sorted, $names);
    }

    public function testAnEmptyOrMissingVendorProducesNothing(): void
    {
        $this->assertSame([], PluginRoot::replaceBlock($this->fixture('core-installed-empty.php')));
        $this->assertSame([], PluginRoot::replaceBlock('/does/not/exist.php'));
    }

    public function testTheRealCoreVendorGeneratesAUsableBlock(): void
    {
        $replace = PluginRoot::replaceBlock();

        $this->assertArrayHasKey('laravel/framework', $replace);
        $this->assertArrayHasKey('illuminate/support', $replace, 'replaced virtuals must be included');
        $this->assertArrayHasKey('psr/log-implementation', $replace, 'provided virtuals must be included');
        $this->assertSame($replace['laravel/framework'], $replace['illuminate/support']);
    }

    public function testNothingIsStaleWithoutPlugins(): void
    {
        $this->assertFalse(PluginRoot::isStale([]), 'an empty manifest');
        $this->assertFalse(
            PluginRoot::isStale(['replace' => ['laravel/framework' => 'v0.0.1']]),
            'a stale replace block with nothing requiring it'
        );
    }

    public function testPluginsResolvedAgainstTheCurrentCoreAreNotStale(): void
    {
        $this->assertFalse(PluginRoot::isStale([
            'require' => ['vendor/plugin' => '^1.0'],
            'replace' => PluginRoot::replaceBlock(),
        ]));
    }

    public function testPluginsResolvedAgainstAnotherCoreAreStale(): void
    {
        $this->assertTrue(PluginRoot::isStale([
            'require' => ['vendor/plugin' => '^1.0'],
            'replace' => ['laravel/framework' => 'v0.0.1'] + PluginRoot::replaceBlock(),
        ]));
    }

    public function testTheCommandsThatManagePluginsRunWithoutThem(): void
    {
        $this->assertTrue(PluginRoot::skipsPlugins(['lnms', 'plugin:sync']));
        $this->assertTrue(PluginRoot::skipsPlugins(['lnms', '-v', 'plugin:remove', 'vendor/plugin']));
        $this->assertTrue(PluginRoot::skipsPlugins(['artisan', '--env=testing', 'plugin:add', 'vendor/plugin:^1.0']));
    }

    public function testEverythingElseLoadsPlugins(): void
    {
        $this->assertFalse(PluginRoot::skipsPlugins(['lnms']));
        $this->assertFalse(PluginRoot::skipsPlugins(['lnms', 'migrate']));
        $this->assertFalse(PluginRoot::skipsPlugins(['lnms', 'plugin:enable', 'MyPlugin']));
        $this->assertFalse(PluginRoot::skipsPlugins(['lnms', 'config:set', 'plugin:sync']));
    }

    /**
     * @return array<string, string>
     */
    private function replaceBlock(): array
    {
        return PluginRoot::replaceBlock($this->fixture('core-installed.php'));
    }

    private function fixture(string $name): string
    {
        return realpath(__DIR__ . '/../../data/plugins') . '/' . $name;
    }
}
