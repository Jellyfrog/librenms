<?php

use Symfony\Component\Yaml\Yaml;

/**
 * Keeps resources/definitions/permissions.yaml in sync with the policies and lang/en/permissions.php
 */
uses(\LibreNMS\Tests\TestCase::class);

/**
 * Checked by a policy, but nothing calls the ability, so they are not offered
 */
const PERMISSIONS_NOT_GRANTABLE = [
    'inventory.delete',
    'inventory.update',
    'port.forceDelete',
    'port.restore',
];

beforeEach(function (): void {
    $this->definitions = Yaml::parseFile(resource_path('definitions/permissions.yaml'));
});

test('groups only contain their own permissions', function (): void {
    foreach ($this->definitions['groups'] as $group => $permissions) {
        foreach ($permissions as $permission) {
            $this->assertStringStartsWith("$group.", $permission);
        }
    }
});

test('every permission has label and description', function (): void {
    foreach (allPermissions() as $permission) {
        [$group, $action] = explode('.', $permission, 2);

        $this->assertNotSame("permissions.$group.title", __("permissions.$group.title"), "$group has no title");
        $this->assertNotSame("permissions.$permission.label", __("permissions.$permission.label"), "$permission has no label");
        $this->assertNotSame("permissions.$permission.description", __("permissions.$permission.description"), "$permission has no description");
    }
});

test('risky permissions exist and explain why', function (): void {
    $this->assertNotEmpty($this->definitions['risky']);
    $this->assertNotSame('permissions.rbac.risky', __('permissions.rbac.risky'));

    foreach ($this->definitions['risky'] as $permission) {
        $this->assertContains($permission, allPermissions(), "$permission is not defined");
        $this->assertNotSame("permissions.$permission.risk", __("permissions.$permission.risk"), "$permission has no risk text");
    }
});

test('only risky permissions have risk text', function (): void {
    foreach (__('permissions') as $group => $translations) {
        foreach ($translations as $action => $translation) {
            if (is_array($translation) && isset($translation['risk'])) {
                $this->assertContains("$group.$action", $this->definitions['risky'], "$group.$action has risk text, but is not listed as risky");
            }
        }
    }
});

test('every policy permission is defined', function (): void {
    $defined = allPermissions();

    foreach (glob(app_path('Policies/*Policy.php')) as $file) {
        $source = file_get_contents($file);
        $prefix = preg_match("/globalPrefix = '([^']+)'/", $source, $matches)
            ? $matches[1]
            : strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', basename($file, 'Policy.php')));

        preg_match_all("/hasGlobalPermission\(\\\$user, '([^']+)'/", $source, $matches);
        foreach (array_unique($matches[1]) as $action) {
            $permission = "$prefix.$action";
            if (! in_array($permission, PERMISSIONS_NOT_GRANTABLE)) {
                $this->assertContains($permission, $defined, basename($file) . " checks $permission");
            }
        }
    }
});

/**
 * @return string[]
 */
function allPermissions(): array
{
    return [...array_merge(...array_values(test()->definitions['groups'])), ...test()->definitions['hidden']];
}
