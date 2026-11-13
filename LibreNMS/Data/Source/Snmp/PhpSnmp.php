<?php

/**
 * PhpSnmp.php
 *
 * Executes SNMP commands using the Net-SNMP CLI utilities.
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
 * @copyright  2026 Steven Wilton
 * @author     Steven Wilton <swilton@fluentit.au>
 */

namespace LibreNMS\Data\Source\Snmp;

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Str;
use LibreNMS\Enum\SnmpOidOutput;
use LibreNMS\Enum\SnmpQuickPrint;
use LibreNMS\Enum\SnmpStringOutput;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Util\Oid;
use LibreNMS\Util\Rewrite;

class PhpSnmp implements SnmpBackendInterface, SnmpTranslatorInterface
{
    private static ?string $loadedMibs = null;

    /**
     * @param  string[]  $oids
     */
    public function get(string $target, array $oids, SnmpConfig $config, SnmpQueryOptions $options): SnmpResponse
    {
        $snmp = $this->buildSnmp($target, $config, $options);

        return $snmp ? $this->runCommand('get', $snmp, $config, $oids, $options) : new NetSnmp()->get($target, $oids, $config, $options);
    }

    public function walk(string $target, string $oid, SnmpConfig $config, SnmpQueryOptions $options): SnmpResponse
    {
        $snmp = $this->buildSnmp($target, $config, $options);

        return $snmp ? $this->runCommand('walk', $snmp, $config, [$oid], $options) : new NetSnmp()->walk($target, $oid, $config, $options);
    }

    /**
     * @param  string[]  $oids
     */
    public function next(string $target, array $oids, SnmpConfig $config, SnmpQueryOptions $options): SnmpResponse
    {
        $snmp = $this->buildSnmp($target, $config, $options);

        return $snmp ? $this->runCommand('getnext', $snmp, $config, $oids, $options) : new NetSnmp()->next($target, $oids, $config, $options);
    }

    public function translate(string $oid, SnmpQueryOptions $options): string
    {
        if (! self::isLibrenmsExtension()) {
            return new NetSnmp()->translate($oid, $options);
        }

        if ($options->oidFormat == SnmpOidOutput::Numeric && new Oid($oid)->isNumeric()) {
            return Str::start($oid, '.');
        }

        $this->initMibs($options, [$oid]);

        // snmp_translate() always looks up bare names like snmptranslate -IR, returns false if it can't
        $result = snmp_translate($oid, match ($options->oidFormat) { /** @phpstan-ignore function.notFound */
            SnmpOidOutput::Numeric => \Snmp\OidOutput::Numeric, /** @phpstan-ignore class.notFound */
            SnmpOidOutput::Module => \Snmp\OidOutput::Module, /** @phpstan-ignore class.notFound */
            default => \Snmp\OidOutput::Suffix, /** @phpstan-ignore class.notFound */
        });

        return is_string($result) ? $result : '';
    }

    /**
     * Build a SNMP object from arguments
     */
    public function buildSnmp(string $target, SnmpConfig $config, SnmpQueryOptions $options): ?\SNMP
    {
        if (! $this->worksFor($config, $options)) {
            return null;
        }

        $snmp = new \SNMP(
            $this->snmpver($config->version),
            $this->hostname($target, $config),
            $this->communityOrUsername($config, $options->context),
            $config->timeout * 1000000,
            $config->retries,
        );

        $snmp->max_oids = max($config->maxOid, 1);
        if (self::isLibrenmsExtension()) {
            $snmp->use_bulk = $config->bulk && $options->allowBulk; /** @phpstan-ignore property.notFound */
        }
        $this->setSecurityOptions($snmp, $config, $options->context);
        $this->setOptions($snmp, $options);

        return $snmp;
    }

    /**
     * librenms-snmp accepts a net-snmp transport prefix, build the same target as the cli
     */
    private function hostname(string $target, SnmpConfig $config): string
    {
        return self::isLibrenmsExtension()
            ? NetSnmpOptions::target($target, $config)
            : Rewrite::addIpv6Brackets($target) . ':' . $config->port;
    }

    /**
     * librenms-snmp adds SNMP::$use_bulk, SNMP::$no_display_hint, SNMP::$quicke_print, snmp_translate(),
     * snmp_init_mib($dirs, $mibs) and udp6/tcp/tcp6 transports to PHP's own extension
     */
    public static function isLibrenmsExtension(): bool
    {
        return defined('SNMP_LIBRENMS_VERSION');
    }

    /**
     * TODO remove the fallback in exec() once librenms-snmp with SNMP::$quicke_print is released
     */
    private static function hasQuickePrint(): bool
    {
        return property_exists(\SNMP::class, 'quicke_print');
    }

    private function communityOrUsername(SnmpConfig $config, string $context): string
    {
        if ($config->version === 'v3') {
            return $config->authname ?: 'root';
        }

        $community = $config->community ?: 'public';

        return $context ? "$community@$context" : $community;
    }

    /**
     * Does php-snmp work for the config/options
     */
    private function worksFor(SnmpConfig $config, SnmpQueryOptions $options): bool
    {
        if (! class_exists(\SNMP::class)) {
            return false;
        }

        // We need to be able to reset MIBs
        if (! function_exists('snmp_init_mib')) {
            return false;
        }

        if ($config->transport !== 'udp' && ! self::isLibrenmsExtension()) {
            return false;
        }

        // walking without GETBULK needs SNMP::$use_bulk (librenms-snmp), v1 walks always use GETNEXT
        if ($config->version !== 'v1' && (! $config->bulk || ! $options->allowBulk) && ! self::isLibrenmsExtension()) {
            return false;
        }

        // disabling display hints needs SNMP::$no_display_hint
        if (! $options->applyDisplayHints && ! self::isLibrenmsExtension()) {
            return false;
        }

        if ($config->version == 'v3') {
            // Dummy SNMP object to check security settings are supported
            $snmp = new \SNMP(\SNMP::VERSION_3, 'localhost', 'root', 1000000, 3);
            try {
                return $this->setSecurityOptions($snmp, $config, $options->context);
            } catch (\Exception|\Error) {
                return false;
            }
        }

        return true;
    }

    private function setSecurityOptions(\SNMP $snmp, SnmpConfig $config, ?string $context): bool
    {
        $authlevel = strtolower((string) $config->authlevel);
        $authalgo = match (strtoupper((string) $config->authalgo)) {
            'SHA-224' => 'SHA224', // librenms-snmp only, php's own extension throws a ValueError and worksFor() falls back
            'SHA-256' => 'SHA256',
            'SHA-384' => 'SHA384', // librenms-snmp only
            'SHA-512' => 'SHA512',
            default => $config->authalgo,
        };
        $cryptoalgo = match (strtoupper((string) $config->cryptoalgo)) {
            'AES-192' => 'AES192',
            'AES-256' => 'AES256',
            'AES-192-C' => 'AES192C',
            'AES-256-C' => 'AES256C',
            default => $config->cryptoalgo,
        };

        if ($authlevel === 'authpriv') {
            return (bool) $snmp->setSecurity('authPriv', $authalgo, $config->authpass, $cryptoalgo, $config->cryptopass, $context ?: '');
        } elseif ($authlevel === 'authnopriv') {
            return (bool) $snmp->setSecurity('authNoPriv', $authalgo, $config->authpass, '', '', $context ?: ''); /** @phpstan-ignore argument.type */
        }

        return (bool) $snmp->setSecurity('noAuthNoPriv', '', '', '', '', $context ?: ''); /** @phpstan-ignore argument.type, argument.type */
    }

    /**
     * Set options on SNMP object
     */
    private function setOptions(\SNMP $snmp, SnmpQueryOptions $options): void
    {
        $snmp->oid_increasing_check = ! $options->tolerateUnorderedIndexes;
        $snmp->quick_print = ($options->quickPrint != SnmpQuickPrint::None);
        $snmp->enum_print = $options->numericEnums;
        $snmp->numeric_index = $options->numericIndexes; /** @phpstan-ignore property.notFound */
        $snmp->numeric_timeticks = $options->numericTimeticks; /** @phpstan-ignore property.notFound */
        $snmp->extended_index = $options->extendedIndex; /** @phpstan-ignore property.notFound */
        $snmp->dont_print_units = ! $options->printUnits; /** @phpstan-ignore property.notFound */
        $snmp->escape_quotes = $options->escapeQuotes; /** @phpstan-ignore property.notFound */
        $snmp->print_hex_text = $options->printHexText; /** @phpstan-ignore property.notFound */
        if (self::isLibrenmsExtension()) {
            $snmp->no_display_hint = ! $options->applyDisplayHints; /** @phpstan-ignore property.notFound */
        }
        if (self::hasQuickePrint()) {
            // -OQ also sets QUICKE_PRINT, which hides the "Wrong Type (should be X): " prefix
            $snmp->quicke_print = ($options->quickPrint == SnmpQuickPrint::Equals); /** @phpstan-ignore property.notFound */
        }
        $snmp->setStringOutputFormat($this->getStringOutput($options->stringFormat)); /** @phpstan-ignore method.notFound */
        $snmp->setOidOutputFormat($this->getOidOutput($options->oidFormat)); /** @phpstan-ignore method.notFound */
    }

    private function snmpver(string $version): int
    {
        return match ($version) {
            'v1' => \SNMP::VERSION_1,
            'v2c' => \SNMP::VERSION_2c,
            'v3' => \SNMP::VERSION_3,
            default => throw new \Exception("SNMP version $version is not supported"),
        };
    }

    private function getOidOutput(SnmpOidOutput $type): \Snmp\OidOutput /** @phpstan-ignore class.notFound */
    {
        return match ($type) {
            SnmpOidOutput::Full => \Snmp\OidOutput::Full, /** @phpstan-ignore class.notFound */
            SnmpOidOutput::Suffix => \Snmp\OidOutput::Suffix, /** @phpstan-ignore class.notFound */
            SnmpOidOutput::Ucd => \Snmp\OidOutput::Ucd, /** @phpstan-ignore class.notFound */
            SnmpOidOutput::Numeric => \Snmp\OidOutput::Numeric, /** @phpstan-ignore class.notFound */
            default => \Snmp\OidOutput::Module, /** @phpstan-ignore class.notFound */
        };
    }

    private function getStringOutput(SnmpStringOutput $type): \Snmp\StringOutput /** @phpstan-ignore class.notFound */
    {
        return match ($type) {
            SnmpStringOutput::Ascii => \Snmp\StringOutput::Ascii, /** @phpstan-ignore class.notFound */
            SnmpStringOutput::Hex => \Snmp\StringOutput::Hex, /** @phpstan-ignore class.notFound */
            default => \Snmp\StringOutput::Guess, /** @phpstan-ignore class.notFound */
        };
    }

    /**
     * @param  string[]  $oids
     */
    private function initMibs(SnmpQueryOptions $options, array $oids): void
    {
        // net-snmp also loads MIBs on demand for oids like MIB::name, the cli forgets them when it exits
        $onDemand = [];
        foreach ($oids as $oid) {
            $module = Oid::of($oid)->getMib();
            if ($module !== '' && ! in_array($module, $options->mibs, true)) {
                $onDemand[$module] = true;
            }
        }
        ksort($onDemand);

        // the MIB tree is process global, only rebuild it when it would differ from the one the cli builds
        $mibDirs = $options->mibDirs ?: [LibrenmsConfig::get('mib_dir')];
        $mibs = array_values(array_filter($options->mibs, fn (string $mib): bool => $mib !== ''));
        $mibKey = serialize([$options->allowUnderscores, $mibDirs, $mibs, array_keys($onDemand)]);
        if ($mibKey === self::$loadedMibs) {
            return;
        }
        self::$loadedMibs = $mibKey;

        // Set the MIB allow underscore options
        snmp_set_mib_option(\Snmp\Mib::AllowUnderscores, $options->allowUnderscores); /** @phpstan-ignore function.notFound, class.notFound */
        if (self::isLibrenmsExtension()) {
            // build the tree from exactly these modules and their imports, like the cli does with -M dirs -m A:B:C
            snmp_init_mib(implode(':', $mibDirs), $mibs); /** @phpstan-ignore function.notFound */

            return;
        }

        // older extension: reset the loaded MIB tree (net-snmp default MIBs) with the configured mib dirs
        snmp_init_mib(implode(':', $mibDirs)); /** @phpstan-ignore function.notFound */

        // Load all explicit MIBs
        foreach ($mibs as $mib) {
            foreach ($mibDirs as $dir) {
                $mibfile = "$dir/$mib";
                if (is_file($mibfile)) {
                    snmp_read_mib($mibfile);

                    break;
                }
            }
        }
    }

    /**
     * Run the command
     *
     * @param  string[]  $oids
     */
    private function runCommand(string $cmd, \SNMP $snmp, SnmpConfig $config, array $oids, SnmpQueryOptions $options): SnmpResponse
    {
        $this->initMibs($options, $oids);

        // PHP-SNMP generates some errors - set the error handler to capture them
        $missing = [];
        $errors = '';
        set_error_handler(function (int $err_severity, string $err_msg, string $err_filename, int $err_line) use (&$missing, &$errors): bool {
            if (preg_match('/\'([^\']+)\': (No Such Object available on this agent at this OID|No Such Instance currently exists at this OID)/', $err_msg, $matches)) {
                $missing[$matches[1]] = $matches[2];
            } elseif (preg_match('/Invalid object identifier: (\S+)/', $err_msg, $matches)) {
                $errors .= "$matches[1]: Unknown Object Identifier\n";
            } else {
                $errors .= "$err_msg\n";
            }

            return true;
        }, E_WARNING);

        $res = match ($cmd) {
            'get' => $snmp->get($oids),
            'getnext' => $snmp->getnext($oids),
            'walk' => $snmp->walk($oids, false, $config->maxRepeaters > 0 ? $config->maxRepeaters : 10, 0),
            default => throw new \Exception("SNMP command $cmd is not supported"),
        };

        restore_error_handler();

        $res = $res ?: [];
        if ($options->quickPrint == SnmpQuickPrint::Equals && ! self::hasQuickePrint()) {
            // without QUICKE_PRINT net-snmp prints the prefix the cli -OQ hides
            $res = (array) preg_replace('/^Wrong Type \(should be [^)]*\): /', '', $res);
        }

        return SnmpResponse::fromPrinted(
            array_replace($res, $missing),
            $errors,
            $errors ? 1 : 0,
            ["php-snmp-$cmd", ...$oids],
            $options,
        );
    }
}
