<?php

/**
 * TopDevicesWidgetTest.php
 *
 * Verifies the Top Devices dashboard widget keeps its configured device group, both when
 * rendering the widget and when rendering its settings form.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Dashboard;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\User;
use App\Models\UserWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;

final class TopDevicesWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private DeviceGroup $group;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');

        $this->user = User::factory()->admin()->create(['enabled' => 1]);
        $this->group = DeviceGroup::factory()->create(['name' => 'Core Routers']);
    }

    public function testSettingsViewKeepsSelectedDeviceGroup(): void
    {
        $widget = $this->createWidget(['device_group' => $this->group->id]);

        $response = $this->actingAs($this->user)->post('ajax/dash/top-devices', [
            'id' => $widget->user_widget_id,
            'settings' => 1,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok', 'show_settings' => 1]);
        $this->assertStringContainsString(
            '<option value="' . $this->group->id . '" selected>Core Routers</option>',
            $response->json('html'),
            'The settings form must pre-select the configured device group, otherwise saving it drops the group'
        );
    }

    public function testWidgetIsFilteredAndTitledByDeviceGroup(): void
    {
        $member = Device::factory()->create(['hostname' => 'member-dev', 'last_polled' => now()]);
        Device::factory()->create(['hostname' => 'outsider-dev', 'last_polled' => now()]);
        $this->group->devices()->attach($member);

        $widget = $this->createWidget(['top_query' => 'uptime', 'device_group' => $this->group->id]);

        $response = $this->actingAs($this->user)->post('ajax/dash/top-devices', [
            'id' => $widget->user_widget_id,
            'settings' => 0,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok', 'show_settings' => 0, 'title' => 'Top Devices (Core Routers)']);
        $html = $response->json('html');
        $this->assertStringContainsString('member-dev', $html);
        $this->assertStringNotContainsString('outsider-dev', $html);
    }

    #[DataProvider('settingsModes')]
    public function testCustomTitleOverridesDeviceGroupTitle(int $settings): void
    {
        $widget = $this->createWidget(['title' => 'My Top Routers', 'device_group' => $this->group->id]);

        $response = $this->actingAs($this->user)->post('ajax/dash/top-devices', [
            'id' => $widget->user_widget_id,
            'settings' => $settings,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok', 'show_settings' => $settings, 'title' => 'My Top Routers']);
    }

    public static function settingsModes(): array
    {
        return [
            'widget' => [0],
            'settings' => [1],
        ];
    }

    private function createWidget(array $settings): UserWidget
    {
        $dashboard = Dashboard::create([
            'user_id' => $this->user->user_id,
            'dashboard_name' => 'Test Dashboard',
            'access' => 0,
        ]);

        return $dashboard->widgets()->create([
            'user_id' => $this->user->user_id,
            'widget' => 'top-devices',
            'col' => 1,
            'row' => 1,
            'size_x' => 6,
            'size_y' => 3,
            'title' => 'Top Devices',
            'refresh' => 60,
            'settings' => $settings,
        ]);
    }
}
