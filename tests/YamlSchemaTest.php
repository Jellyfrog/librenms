<?php

/**
 * YamlSchemaTest.php
 *
 * Verifies yaml files conform to the schema definitions
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
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <librenms+n@laf.io>
 */

use Illuminate\Support\Str;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\JsonDecodingException;
use JsonSchema\Exception\ValidationException;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

uses(\LibreNMS\Tests\TestCase::class)->group('yaml');

const YAML_SCHEMA_EXCLUDED = [
    '/os_detection/default.yaml',
    '/os_detection/generic.yaml',
    '/os_detection/ping.yaml',
];


test('config schema', function () {
    $error = validateFileAgainstSchema(resource_path('definitions/config_definitions.json'), resource_path('definitions/schema/config_schema.json'));

    expect($error)->toBeNull((string) $error);
});

test('osdefinition schema', function () {
    validateYamlFilesAgainstSchema(resource_path('definitions/os_detection'), resource_path('definitions/schema/os_schema.json'));
});

test('osmatch filename', function () {
    foreach (listFiles(resource_path('definitions/os_detection/*.yaml')) as $filename => $file) {
        expect(substr((string) $filename, 0, -5))->toEqual(Yaml::parseFile($file)['os'], "Parameter 'os' doesn't match the filename $filename");
    }
});

test('discovery definition schema', function () {
    validateYamlFilesAgainstSchema(resource_path('definitions/os_discovery'), resource_path('definitions/schema/discovery_schema.json'));
});

function validateYamlFilesAgainstSchema(string $dir, string $schema_file): void
{
    $errors = [];

    foreach (listFiles($dir . '/*.yaml') as $file) {
        $error = validateFileAgainstSchema($file, $schema_file);
        if ($error) {
            $errors[] = $error;
        }
    }

    $count = count($errors);
    expect($errors)->toBeEmpty(implode("\n", $errors) . "\nFiles with errors: $count\n\n");
}

function listFiles($pattern): array
{
    return collect(glob($pattern))
        ->reduce(function ($array, $file) {
            if (Str::contains($file, YAML_SCHEMA_EXCLUDED)) {
                return $array;
            }

            $name = basename($file);
            $array[$name] = $file;

            return $array;
        }, []);
}

/**
 * @param  string  $filePath
 * @param  string  $schema_file  full path
 */
function validateFileAgainstSchema(string $filePath, string $schema_file): ?string
{
    $schema = (object) ['$ref' => 'file://' . $schema_file];
    $filename = basename($filePath);

    try {
        $data = str_ends_with($filePath, '.json')
        ? json_decode(file_get_contents($filePath))
        : Yaml::parse(file_get_contents($filePath));
    } catch (ParseException $e) {
        throw new ExpectationFailedException("$filePath Could not be parsed", null, $e);
    }

    try {
        $validator = new \JsonSchema\Validator;
        $validator->validate(
            $data,
            $schema,
            Constraint::CHECK_MODE_TYPE_CAST | Constraint::CHECK_MODE_VALIDATE_SCHEMA | Constraint::CHECK_MODE_EXCEPTIONS
        );
    } catch (JsonDecodingException|ValidationException $e) {
        // Output the filename so we know what file failed
        $error = $e->getMessage();
        if (str_contains($error, 'Error validating /discovery/')) {
            $error = 'Discovery must contain an identifier sysObjectID or sysDescr';
        }

        return "$filename failed to validate against $schema_file\n\n$error";
    }

    if (! $validator->isValid()) {
        $errors = collect($validator->getErrors())
            ->reduce(fn ($out, $error) => sprintf("%s[%s] %s\n", $out, $error['property'], $error['message']), '');

        return "$filename failed to validate against $schema_file\n\n$errors";
    }

    return null;
}