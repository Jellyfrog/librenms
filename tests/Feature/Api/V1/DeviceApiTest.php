<?php

namespace LibreNMS\Tests\Feature\Api\V1;

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use App\Models\UserPref;
use PHPUnit\Framework\Attributes\DataProvider;

final class DeviceApiTest extends ApiV1TestCase
{
    private const CREDENTIAL_FIELDS = ['community', 'authlevel', 'authname', 'authpass', 'authalgo', 'cryptopass', 'cryptoalgo', 'snmpver', 'port', 'transport', 'timeout', 'retries'];

    public function testAdminListsDevicesWithApiFieldNames(): void
    {
        $device = Device::factory()->create([
            'sysName' => 'core-sw-1',
            'community' => 'secret-community',
            'authpass' => 'secret-authpass',
            'cryptopass' => 'secret-cryptopass',
        ]);

        $response = $this->getApi('/api/v1/devices', $this->admin())->assertOk();

        $data = $response->collect()->firstWhere('id', $device->device_id);
        $this->assertNotNull($data, 'The created device is in the list');
        $this->assertSame($device->hostname, $data['hostname']);
        $this->assertSame('core-sw-1', $data['sys_name']);

        // the API names, not the column names
        $this->assertArrayNotHasKey('device_id', $data);
        $this->assertArrayNotHasKey('sysName', $data);

        foreach (self::CREDENTIAL_FIELDS as $field) {
            $this->assertArrayNotHasKey($field, $data);
        }
        $response->assertDontSee('secret-');
    }

    public function testShowsSingleDevice(): void
    {
        $device = Device::factory()->create([
            'sysName' => 'core-sw-1',
            'features' => 'ADVENTERPRISEK9',
            'notes' => 'rack 4',
            'community' => 'secret-community',
            'status' => true,
            'last_polled' => '2026-10-01 12:00:00',
        ]);

        $response = $this->getApi("/api/v1/devices/$device->device_id", $this->admin())->assertOk();

        $response->assertJsonPath('id', $device->device_id)
            ->assertJsonPath('sys_name', 'core-sw-1')
            ->assertJsonPath('feature', 'ADVENTERPRISEK9')
            ->assertJsonPath('note', 'rack 4')
            ->assertJsonPath('status', true)
            ->assertJsonMissingPath('community')
            ->assertJsonMissingPath('features')
            ->assertJsonMissingPath('notes')
            ->assertDontSee('secret-');
        $this->assertStringStartsWith('2026-10-01T12:00:00', $response->json('last_polled'));
    }

    public function testUnknownDeviceIsNotFound(): void
    {
        $this->getApi('/api/v1/devices/999999999', $this->admin())->assertNotFound();
    }

    public function testLimitedUserOnlySeesTheirDevices(): void
    {
        $mine = Device::factory()->create();
        $other = Device::factory()->create();
        $user = $this->limitedUser($mine);

        $ids = $this->getApi('/api/v1/devices', $user)->assertOk()->collect()->pluck('id')->all();

        $this->assertSame([$mine->device_id], $ids);

        $this->getApi("/api/v1/devices/$mine->device_id", $user)->assertOk();
        $this->getApi("/api/v1/devices/$other->device_id", $user)->assertNotFound();
    }

    public function testFiltersDevices(): void
    {
        $match = Device::factory()->create(['hostname' => 'edge-router-7.example.com']);
        Device::factory()->create(['hostname' => 'core-sw-1.example.com']);

        $ids = $this->getApi('/api/v1/devices?hostname=router', $this->admin())->assertOk()->collect()->pluck('id')->all();

        $this->assertSame([$match->device_id], $ids);
    }

    public function testOrdersDevices(): void
    {
        Device::factory()->create(['hostname' => 'aaa.example.com']);
        Device::factory()->create(['hostname' => 'zzz.example.com']);
        $admin = $this->admin();

        $asc = $this->getApi('/api/v1/devices?order[hostname]=asc', $admin)->assertOk()->collect()->pluck('hostname');
        $desc = $this->getApi('/api/v1/devices?order[hostname]=desc', $admin)->assertOk()->collect()->pluck('hostname');

        $this->assertSame('aaa.example.com', $asc->first());
        $this->assertSame('zzz.example.com', $desc->first());
    }

    public function testDefaultFormatIsJsonApiWithPagination(): void
    {
        Device::factory()->count(3)->create();

        // what curl sends
        $response = $this->get('/api/v1/devices?itemsPerPage=2', $this->headers($this->admin()) + ['Accept' => '*/*'])->assertOk();

        $this->assertStringStartsWith('application/vnd.api+json', $response->headers->get('Content-Type'));
        $response->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'Device')
            ->assertJsonPath('meta.totalItems', Device::count())
            ->assertJsonPath('meta.itemsPerPage', 2);
        $this->assertContains((int) $response->json('data.0.id'), Device::pluck('device_id')->all());
        $this->assertArrayHasKey('hostname', $response->json('data.0.attributes'));
    }

    public function testFormatCanBeChosenWithAnExtension(): void
    {
        $device = Device::factory()->create();
        $headers = $this->headers($this->admin()) + ['Accept' => '*/*'];

        $json = $this->get("/api/v1/devices/$device->device_id.json", $headers)->assertOk()->assertJsonPath('id', $device->device_id);
        $this->assertStringStartsWith('application/json', $json->headers->get('Content-Type'));

        auth()->forgetGuards();
        $jsonApi = $this->get('/api/v1/devices.jsonapi', $headers)->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertStringStartsWith('application/vnd.api+json', $jsonApi->headers->get('Content-Type'));

        auth()->forgetGuards();
        $this->get('/api/v1/devices.csv', $headers)->assertNotFound();
    }

    #[DataProvider('formats')]
    public function testEveryFormatIsServedScopedAndWithoutCredentials(string $extension, string $contentType): void
    {
        $location = Location::factory()->create();
        $mine = Device::factory()->create(['sysName' => 'core-sw-1', 'community' => 'secret-community', 'location_id' => $location->id]);
        $other = Device::factory()->create(['sysName' => 'not-for-this-user', 'location_id' => $location->id]);
        $headers = $this->headers($this->limitedUser($mine)) + ['Accept' => '*/*'];

        foreach (['/api/v1/devices', "/api/v1/devices/$mine->device_id", "/api/v1/locations/$location->id", "/api/v1/locations/$location->id/devices"] as $uri) {
            auth()->forgetGuards();
            $response = $this->get("$uri.$extension", $headers)->assertOk();

            $this->assertStringStartsWith($contentType, $response->headers->get('Content-Type'), $uri);
            $response->assertDontSee('secret-community')
                ->assertDontSee('not-for-this-user')
                ->assertDontSee("devices/$other->device_id");
        }

        auth()->forgetGuards();
        $this->get("/api/v1/devices/$mine->device_id.$extension", $headers)->assertSee('core-sw-1');
        auth()->forgetGuards();
        $this->get("/api/v1/devices/$other->device_id.$extension", $headers)->assertNotFound();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function formats(): array
    {
        return [
            'JSON:API' => ['jsonapi', 'application/vnd.api+json'],
            'JSON' => ['json', 'application/json'],
            'JSON-LD' => ['jsonld', 'application/ld+json'],
            'HAL' => ['jsonhal', 'application/hal+json'],
        ];
    }

    public function testRequiresBearerToken(): void
    {
        $token = $this->admin()->createToken('test')->plainTextToken;

        $this->json('GET', '/api/v1/devices')->assertUnauthorized();
        $this->get('/api/v1/devices', ['Accept' => '*/*'])->assertUnauthorized();
        $this->json('GET', '/api/v1/devices', [], ['X-Auth-Token' => $token])->assertUnauthorized();
        $this->json('GET', "/api/v1/devices?api_token=$token")->assertUnauthorized();
    }

    public function testWebSessionAuthenticates(): void
    {
        $device = Device::factory()->create();

        $this->withWebSession($this->admin())
            ->json('GET', "/api/v1/devices/$device->device_id")
            ->assertOk()
            ->assertJsonPath('id', $device->device_id);
    }

    public function testWebSessionNeedsCompletedTwoFactor(): void
    {
        LibrenmsConfig::set('twofactor', true);
        $user = $this->admin();
        UserPref::setPref($user, 'twofactor', ['key' => 'ABCDEFGHIJKLMNOP', 'fails' => 0, 'last' => 0, 'counter' => false]);

        // password accepted, second factor not entered yet
        $this->withWebSession($user)->json('GET', '/api/v1/devices')->assertRedirect('/2fa');

        auth()->forgetGuards();
        $this->withWebSession($user, ['twofactor' => true])->json('GET', '/api/v1/devices')->assertOk();
    }

    public function testWebSessionOfDisabledUserIsRejected(): void
    {
        /** @var User $user */
        $user = User::factory()->admin()->create(['enabled' => false]);

        $this->withWebSession($user)->json('GET', '/api/v1/devices')->assertRedirect();
    }

    public function testDocsAreHtmlForBrowsersAndOpenApiForClients(): void
    {
        $headers = $this->headers($this->admin());

        $html = $this->get('/api/v1/docs', $headers + ['Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8'])->assertOk();
        $this->assertStringStartsWith('text/html', $html->headers->get('Content-Type'));
        $html->assertSee('swagger-ui-bundle.js');

        auth()->forgetGuards();
        $this->get('/api/v1/docs', $headers + ['Accept' => '*/*'])
            ->assertOk()
            ->assertJsonPath('info.title', 'LibreNMS API');

        // a resource stays data in a browser
        auth()->forgetGuards();
        $data = $this->get('/api/v1/devices', $headers + ['Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8'])->assertOk();
        $this->assertStringStartsWith('application/vnd.api+json', $data->headers->get('Content-Type'));
    }

    public function testUserWithoutDevicePermissionIsForbidden(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->getApi('/api/v1/devices', $user)->assertForbidden();
    }

    public function testNotFoundWhenV1Disabled(): void
    {
        LibrenmsConfig::set('api.v1.enabled', false);

        $this->getApi('/api/v1/devices', $this->admin())->assertNotFound();
        // without a token as well: a disabled API does not reveal itself with a 401
        auth()->forgetGuards();
        $this->json('GET', '/api/v1/devices')->assertNotFound();
        $this->json('GET', '/api/v1/system')->assertNotFound();
        $this->json('GET', '/api/v1/health')->assertNotFound();
    }

    public function testHealthIsPublic(): void
    {
        $this->json('GET', '/api/v1/health')
            ->assertOk()
            ->assertExactJson(['meta' => ['status' => 'healthy']]);
    }

    public function testSystemReportsVersionAndCounts(): void
    {
        $this->json('GET', '/api/v1/system')->assertUnauthorized();

        $mine = Device::factory()->create();
        Device::factory()->create();

        $this->getApi('/api/v1/system', $this->admin())
            ->assertOk()
            ->assertJsonPath('name', 'LibreNMS')
            ->assertJsonPath('version', \LibreNMS\Util\Version::VERSION)
            ->assertJsonPath('device_count', Device::count());

        // the counts are of what the user may access
        $this->getApi('/api/v1/system', $this->limitedUser($mine))->assertOk()->assertJsonPath('device_count', 1);
    }
}
