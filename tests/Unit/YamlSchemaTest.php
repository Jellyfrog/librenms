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

namespace LibreNMS\Tests\Unit;

use Illuminate\Support\Str;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\JsonDecodingException;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

#[Group('yaml')]
final class YamlSchemaTest extends TestCase
{
    private array $excluded = [
        '/os_detection/default.yaml',
        '/os_detection/generic.yaml',
        '/os_detection/ping.yaml',
    ];

    public function testConfigSchema(): void
    {
        $error = $this->validateFileAgainstSchema(resource_path('definitions/config_definitions.json'), resource_path('definitions/schema/config_schema.json'), new Validator);

        $this->assertNull($error, (string) $error);
    }

    #[TestDox('OS definition schema')]
    public function testOSDefinitionSchema(): void
    {
        $this->validateYamlFilesAgainstSchema(resource_path('definitions/os_detection'), resource_path('definitions/schema/os_schema.json'));
    }

    #[TestDox('OS match filename')]
    public function testOSMatchFilename(): void
    {
        foreach ($this->listFiles(resource_path('definitions/os_detection/*.yaml')) as $filename => $file) {
            $this->assertEquals(
                Yaml::parseFile($file)['os'],
                substr((string) $filename, 0, -5),
                "Parameter 'os' doesn't match the filename $filename"
            );
        }
    }

    public function testDiscoveryDefinitionSchema(): void
    {
        $this->validateYamlFilesAgainstSchema(resource_path('definitions/os_discovery'), resource_path('definitions/schema/discovery_schema.json'));
    }

    #[TestDox('State names have a single set of states per OS')]
    public function testStateNamesHaveOneSetOfStatesPerOs(): void
    {
        $conflicts = [];

        foreach ($this->listFiles(resource_path('definitions/os_discovery/*.yaml')) as $filename => $file) {
            $os = substr((string) $filename, 0, -5);
            $seen = [];

            foreach (Yaml::parseFile($file)['modules']['sensors']['state']['data'] ?? [] as $sensor) {
                if (! isset($sensor['states'])) {
                    continue;
                }

                // state names are stored once per os, so every use of a name must translate values the same way
                $stateName = strtolower((string) ($sensor['state_name'] ?? $sensor['oid']));
                $states = array_column(array_map(fn ($state) => [$state['value'], $state['descr'] . ' (' . $state['generic'] . ')'], $sensor['states']), 1, 0);
                ksort($states);

                $seen[$stateName] ??= $states;
                if ($seen[$stateName] !== $states) {
                    $conflicts[$os][$stateName] = $sensor['state_name'] ?? $sensor['oid'];
                }
            }
        }

        $messages = [];
        foreach ($conflicts as $os => $stateNames) {
            foreach (array_diff($stateNames, $this->conflictingStateNames[$os] ?? []) as $stateName) {
                $messages[] = "$os.yaml: state_name '$stateName' is defined with different states, use a distinct state_name for each set of states";
            }
        }
        foreach ($this->conflictingStateNames as $os => $stateNames) {
            foreach (array_diff($stateNames, $conflicts[$os] ?? []) as $stateName) {
                $messages[] = "$os.yaml: state_name '$stateName' no longer conflicts, remove it from \$conflictingStateNames";
            }
        }

        $this->assertEmpty($messages, implode("\n", $messages));
    }

    private function validateYamlFilesAgainstSchema(string $dir, string $schema_file): void
    {
        $errors = [];
        // reuse one validator so the schema is loaded and checked once, not per file
        $validator = new Validator;

        foreach ($this->listFiles($dir . '/*.yaml') as $file) {
            $error = $this->validateFileAgainstSchema($file, $schema_file, $validator);
            if ($error) {
                $errors[] = $error;
            }
        }

        $count = count($errors);
        $this->assertEmpty($errors, implode("\n", $errors) . "\nFiles with errors: $count\n\n");
    }

    /**
     * State names that are defined with different states inside one os definition.
     * Sensors using these can show the states of the other definition, do not add to this list, fix them instead.
     *
     * @var array<string, string[]>
     */
    private array $conflictingStateNames = [
        'eltex-mes23xx' => ['rlPhdUnitEnvParamEntry'],
        'microsens-g6' => ['statusOverallStatus'],
        'morningstar-mppt' => ['chargeState', 'loadState'],
        'moxa-etherdevice' => ['couplingMode', 'turboChainPort1Status', 'turboChainPort2Status'],
        'panos' => ['panSysHaStateEnum'],
        'raritan-pdu' => ['PDU2-MIB::externalSensorMeasurementsTable'],
        'samlex' => ['transferTypes'],
        'ssu2000' => ['staClkTable'],
        'uhp' => ['Current redundancy state'],
    ];

    private function listFiles($pattern): array
    {
        return collect(glob($pattern))
            ->reduce(function ($array, $file) {
                if (Str::contains($file, $this->excluded)) {
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
     * @param  Validator  $validator  reuse a validator to avoid reloading the schema
     */
    private function validateFileAgainstSchema(string $filePath, string $schema_file, Validator $validator): ?string
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
}
