<?php

uses(\LibreNMS\Tests\TestCase::class);
use Illuminate\Support\Facades\Lang;
use LibreNMS\Enum\DeviceType;

test('enum matches os schema', function (): void {
    $schema = json_decode(file_get_contents(resource_path('definitions/schema/os_schema.json')), true);

    expect($schema['properties']['type']['enum'])->toEqualCanonicalizing(array_column(DeviceType::cases(), 'value'), 'DeviceType enum and os_schema.json type enum differ');
});

test('every type has translation', function (): void {
    foreach (DeviceType::cases() as $type) {
        expect(Lang::has('device.types.' . $type->value, 'en', false))->toBeTrue("Missing translation device.types.$type->value");
    }
});
