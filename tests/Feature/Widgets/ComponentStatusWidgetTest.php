<?php

/**
 * ComponentStatusWidgetTest.php
 *
 * Verifies the Component Status dashboard widget counts enabled components by status
 * and ignores components that have been disabled.
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

namespace LibreNMS\Tests\Feature\Widgets;

use App\Models\Component;
use App\Models\Dashboard;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use LibreNMS\Tests\TestCase;

final class ComponentStatusWidgetTest extends TestCase
{
    use RefreshDatabase;

    private const OK = 0;
    private const WARNING = 1;
    private const CRITICAL = 2;

    public function testCountsEnabledComponentsByStatus(): void
    {
        $device = Device::factory()->create();
        $this->createComponents($device, self::OK, 3);
        $this->createComponents($device, self::WARNING, 2);
        $this->createComponents($device, self::CRITICAL, 1);

        $response = $this->actingAs($this->admin())
            ->post('ajax/dash/component-status')
            ->assertOk();

        $this->assertSame(['Ok' => 3, 'Warning' => 2, 'Critical' => 1], $this->renderedTotals($response));
    }

    public function testDisabledComponentsAreNotCounted(): void
    {
        $device = Device::factory()->create();
        $this->createComponents($device, self::OK, 1);
        $this->createComponents($device, self::CRITICAL, 1);
        $this->createComponents($device, self::WARNING, 1, disabled: true);
        $this->createComponents($device, self::CRITICAL, 2, disabled: true);

        $response = $this->actingAs($this->admin())
            ->post('ajax/dash/component-status')
            ->assertOk();

        $this->assertSame(['Ok' => 1, 'Warning' => 0, 'Critical' => 1], $this->renderedTotals($response));
    }

    public function testDeviceGroupFilterCountsOnlyEnabledComponentsOfGroupDevices(): void
    {
        $user = $this->admin();
        $grouped = Device::factory()->create();
        $ungrouped = Device::factory()->create();

        $group = DeviceGroup::factory()->create();
        $group->devices()->attach($grouped->device_id);

        $this->createComponents($grouped, self::CRITICAL, 1);
        $this->createComponents($grouped, self::OK, 1, disabled: true);
        $this->createComponents($ungrouped, self::OK, 2);
        $this->createComponents($ungrouped, self::WARNING, 1);

        $dashboard = Dashboard::create(['user_id' => $user->user_id, 'dashboard_name' => 'Test', 'access' => 0]);
        $widget = $dashboard->widgets()->create([
            'user_id' => $user->user_id,
            'widget' => 'component-status',
            'col' => 1,
            'row' => 1,
            'size_x' => 6,
            'size_y' => 3,
            'title' => 'Component Status',
            'refresh' => 60,
            'settings' => ['device_group' => $group->id],
        ]);

        $response = $this->actingAs($user)
            ->post('ajax/dash/component-status', ['id' => $widget->user_widget_id])
            ->assertOk();

        $this->assertSame(['Ok' => 0, 'Warning' => 0, 'Critical' => 1], $this->renderedTotals($response));
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['enabled' => 1]);
    }

    private function createComponents(Device $device, int $status, int $count, bool $disabled = false): void
    {
        Component::factory()->count($count)->create([
            'device_id' => $device->device_id,
            'status' => $status,
            'disabled' => (int) $disabled,
        ]);
    }

    /**
     * Read the status => count rows rendered in the widget html
     *
     * @return array<string, int>
     */
    private function renderedTotals(TestResponse $response): array
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($response->json('html'), LIBXML_NOERROR | LIBXML_NOWARNING);

        $totals = [];
        foreach ($dom->getElementsByTagName('tr') as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length === 2) {
                $totals[trim($cells->item(0)->textContent)] = (int) trim($cells->item(1)->textContent);
            }
        }

        return $totals;
    }
}
