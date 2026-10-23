<?php

namespace LibreNMS\Tests\Browser;

use App\Facades\LibrenmsConfig;
use App\Models\Config;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use LibreNMS\Tests\DuskTestCase;
use PHPUnit\Framework\Attributes\Group;
use Spatie\Permission\Models\Role;

#[Group('browser')]
final class SettingsPageTest extends DuskTestCase
{
    use DatabaseTruncation;
    /** @var list<string> */
    protected array $connectionsToTruncate = ['testing', 'testing_persistent'];

    /**
     * Nested sub-keyed values must not be saved as "[object Object]" (#21015)
     *
     * @throws \Throwable
     */
    public function testNestedSubKeyedValueIsEditedAsJson(): void
    {
        $attributeMap = ['email' => 'email', 'groups' => 'groups'];
        LibrenmsConfig::persist('auth.socialite.configs', ['saml2' => ['client_id' => 'foo', 'attribute_map' => $attributeMap]]);
        Role::findOrCreate('admin');
        $user = User::factory()->admin()->create(['enabled' => 1]);

        $this->browse(function (Browser $browser) use ($user, $attributeMap): void {
            $field = '@saml2.attribute_map';
            $json = json_encode($attributeMap);

            $browser->loginAs($user)
                ->visit('/settings/auth/socialite/auth.socialite.configs')
                ->waitFor($field)
                ->assertInputValue($field, $json)
                // leaving the field unchanged does not save it
                ->click($field)
                ->keys($field, '{tab}')
                // invalid json is rejected and the field is reset, select all instead of clear(), which saves on blur
                ->keys($field, ['{control}', 'a'], '[object Object]', '{tab}')
                ->waitFor('.toast-error')
                ->assertInputValue($field, $json)
                ->pause(1000); // longer than the save debounce
            $this->assertSame($attributeMap, $this->savedAttributeMap());

            // valid json is saved as an array, leading space so keys() doesn't parse it as a {key}
            $browser->keys($field, ['{control}', 'a'], ' {"email":"mail"}', '{tab}')
                ->waitFor('.has-success');
            $this->assertSame(['email' => 'mail'], $this->savedAttributeMap());
        });
    }

    private function savedAttributeMap(): ?array
    {
        return Config::firstWhere('config_name', 'auth.socialite.configs')?->config_value['saml2']['attribute_map'] ?? null;
    }
}
