<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Data\Source\Snmp\NetSnmpOptions;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Enum\SnmpOidOutput;
use LibreNMS\Enum\SnmpQuickPrint;
use LibreNMS\Enum\SnmpStringOutput;

test('default options', function () {
    $options = new SnmpQueryOptions();

    expect($options->context)->toBe('');
    expect($options->mibs)->toContain('SNMPv2-MIB');
    expect($options->mibDirs)->toBe([]);
    expect($options->allowBulk)->toBeTrue();
    expect($options->tolerateUnorderedIndexes)->toBeFalse();

    expect($options->oidFormat)->toBe(SnmpOidOutput::Module);
    expect($options->numericIndexes)->toBeFalse();

    expect($options->stringFormat)->toBe(SnmpStringOutput::Guess);
    expect($options->numericEnums)->toBeFalse();
    expect($options->numericTimeticks)->toBeFalse();
    expect($options->printUnits)->toBeTrue();
    expect($options->applyDisplayHints)->toBeTrue();

    expect($options->quickPrint)->toBe(SnmpQuickPrint::None);
    expect($options->valueOnly)->toBeFalse();
    expect($options->extendedIndex)->toBeFalse();
    expect($options->allowUnderscores)->toBeFalse();
});

test('quick print defaults', function () {
    $options = SnmpQueryOptions::quickPrint();

    expect($options->context)->toBe('');
    expect($options->mibs)->toContain('SNMPv2-MIB');
    expect($options->mibDirs)->toBe([]);
    expect($options->allowBulk)->toBeTrue();
    expect($options->tolerateUnorderedIndexes)->toBeFalse();

    expect($options->oidFormat)->toBe(SnmpOidOutput::Module);
    expect($options->numericIndexes)->toBeFalse();

    expect($options->stringFormat)->toBe(SnmpStringOutput::Guess);
    expect($options->numericEnums)->toBeTrue();
    expect($options->numericTimeticks)->toBeTrue();
    expect($options->printUnits)->toBeFalse();
    expect($options->applyDisplayHints)->toBeTrue();

    expect($options->quickPrint)->toBe(SnmpQuickPrint::Equals);
    expect($options->valueOnly)->toBeFalse();
    expect($options->extendedIndex)->toBeTrue();
    expect($options->allowUnderscores)->toBeFalse();
});

test('parse cli null sets defaults', function () {
    $options = (new NetSnmpOptions)->parseCli(null);

    expect($options->context)->toBe('');
    expect($options->allowBulk)->toBeTrue();
    expect($options->tolerateUnorderedIndexes)->toBeFalse();
    expect($options->oidFormat)->toBe(SnmpOidOutput::Module);
    expect($options->numericIndexes)->toBeFalse();
    expect($options->numericEnums)->toBeTrue();
    expect($options->numericTimeticks)->toBeTrue();
    expect($options->stringFormat)->toBe(SnmpStringOutput::Guess);
    expect($options->printUnits)->toBeFalse();
    expect($options->applyDisplayHints)->toBeTrue();
    expect($options->quickPrint)->toBe(SnmpQuickPrint::Equals);
    expect($options->extendedIndex)->toBeTrue();
    expect($options->allowUnderscores)->toBeFalse();
});

test('parse cli ouneb', function () {
    $options = (new NetSnmpOptions)->parseCli(['-OUneb']);

    expect($options->oidFormat)->toBe(SnmpOidOutput::Numeric);
    expect($options->numericIndexes)->toBeTrue();
    expect($options->numericEnums)->toBeTrue();
    expect($options->stringFormat)->toBe(SnmpStringOutput::Guess);
    expect($options->printUnits)->toBeFalse();
    expect($options->quickPrint)->toBe(SnmpQuickPrint::None);
    expect($options->extendedIndex)->toBeFalse();
    expect($options->numericTimeticks)->toBeFalse();
    expect($options->allowUnderscores)->toBeFalse();
});

test('parse cli with pu preserves disallow underscores', function () {
    $options = (new NetSnmpOptions)->parseCli(['-OteQUsab', '-Pu']);

    expect($options->allowUnderscores)->toBeTrue();
    expect($options->numericTimeticks)->toBeTrue();
    expect($options->numericEnums)->toBeTrue();
    expect($options->quickPrint)->toBe(SnmpQuickPrint::Equals);
    expect($options->printUnits)->toBeFalse();
    expect($options->numericIndexes)->toBeTrue();
    expect($options->stringFormat)->toBe(SnmpStringOutput::Ascii);
});

test('parse cli hex strings precedence', function () {
    // When 'x' comes after 'a', hexStrings wins
    $optionsX = (new NetSnmpOptions)->parseCli(['-OteQUax']);
    expect($optionsX->stringFormat)->toBe(SnmpStringOutput::Hex);

    // When 'a' comes after 'x', asciiStrings wins
    $optionsA = (new NetSnmpOptions)->parseCli(['-OteQUxa']);
    expect($optionsA->stringFormat)->toBe(SnmpStringOutput::Ascii);

    // Standard -OQUsx
    $optionsOnlyX = (new NetSnmpOptions)->parseCli(['-OQUsx']);
    expect($optionsOnlyX->stringFormat)->toBe(SnmpStringOutput::Hex);
});

test('parse cli numeric and symbolic precedence', function () {
    // When 's' comes after 'n', symbolic without MIB wins
    $optionsS = (new NetSnmpOptions)->parseCli(['-Ons']);
    expect($optionsS->oidFormat)->toBe(SnmpOidOutput::Suffix);

    // When 'n' comes after 's', numeric wins
    $optionsN = (new NetSnmpOptions)->parseCli(['-Osn']);
    expect($optionsN->oidFormat)->toBe(SnmpOidOutput::Numeric);

    // When 'S' comes after 'n', symbolic with MIB wins
    $optionsUpperS = (new NetSnmpOptions)->parseCli(['-OnS']);
    expect($optionsUpperS->oidFormat)->toBe(SnmpOidOutput::Module);
});

test('parse cli display hints', function () {
    $options = (new NetSnmpOptions)->parseCli(['-OUneb', '-Ih']);
    expect($options->applyDisplayHints)->toBeFalse();

    $optionsNormal = (new NetSnmpOptions)->parseCli(['-OUneb']);
    expect($optionsNormal->applyDisplayHints)->toBeTrue();
});

test('parse cli unordered indexes', function () {
    $optionsC = (new NetSnmpOptions)->parseCli(['-Cc']);
    expect($optionsC->tolerateUnorderedIndexes)->toBeTrue();
});

test('parse cli mib visibility', function () {
    $hideMib = (new NetSnmpOptions)->parseCli('-OQUs');
    expect($hideMib->oidFormat)->toBe(SnmpOidOutput::Suffix);
    expect($hideMib->numericEnums)->toBeFalse();

    $showMib = (new NetSnmpOptions)->parseCli(['-OQUS']);
    expect($showMib->oidFormat)->toBe(SnmpOidOutput::Module);
});

test('parse cli mibs append', function () {
    $optionsSeparate = (new NetSnmpOptions)->parseCli(['-m', '+EXTRA-MIB']);
    expect($optionsSeparate->mibs)->toContain('EXTRA-MIB');
    expect($optionsSeparate->mibs)->toContain('SNMPv2-MIB');

    $optionsCombined = (new NetSnmpOptions)->parseCli(['-m+ANOTHER-MIB']);
    expect($optionsCombined->mibs)->toContain('ANOTHER-MIB');
});

test('parse cli mibs replace', function () {
    $optionsSeparate = (new NetSnmpOptions)->parseCli(['-m', 'MY-MIB:YOUR-MIB']);
    expect($optionsSeparate->mibs)->toBe(['MY-MIB', 'YOUR-MIB']);

    $optionsCombined = (new NetSnmpOptions)->parseCli(['-mFIRST-MIB:SECOND-MIB']);
    expect($optionsCombined->mibs)->toBe(['FIRST-MIB', 'SECOND-MIB']);
});

test('parse cli mib dirs', function () {
    $optionsSeparate = (new NetSnmpOptions)->parseCli(['-M', '/opt/mibs:/extra/mibs']);
    expect($optionsSeparate->mibDirs)->toBe(['/opt/mibs', '/extra/mibs']);

    $optionsCombined = (new NetSnmpOptions)->parseCli(['-M/single/dir']);
    expect($optionsCombined->mibDirs)->toBe(['/single/dir']);
});

test('parse cli accepts single string', function () {
    $options = (new NetSnmpOptions)->parseCli('-OUneb');
    expect($options->oidFormat)->toBe(SnmpOidOutput::Numeric);
    expect($options->numericIndexes)->toBeTrue();
    expect($options->numericEnums)->toBeTrue();
});

test('parse cli supports qand voptions', function () {
    $parser = new NetSnmpOptions();

    $optionsUpperQ = $parser->parseCli(['-OQ']);
    expect($optionsUpperQ->quickPrint)->toBe(SnmpQuickPrint::Equals);
    expect($optionsUpperQ->valueOnly)->toBeFalse();
    expect($parser->buildOutputFlags($optionsUpperQ))->toContain('-OQ');

    $optionsLowerQ = $parser->parseCli(['-Oq']);
    expect($optionsLowerQ->quickPrint)->toBe(SnmpQuickPrint::NoEquals);
    expect($optionsLowerQ->valueOnly)->toBeFalse();
    expect($parser->buildOutputFlags($optionsLowerQ))->toContain('-Oq');

    $optionsV = $parser->parseCli(['-Ov']);
    expect($optionsV->valueOnly)->toBeTrue();
    expect($optionsV->oidFormat)->toBe(SnmpOidOutput::Module);
    expect($parser->buildOutputFlags($optionsV))->toContain('-Ov');

    $optionsQv = $parser->parseCli(['-Oqv']);
    expect($optionsQv->quickPrint)->toBe(SnmpQuickPrint::NoEquals);
    expect($optionsQv->valueOnly)->toBeTrue();
    expect($optionsQv->oidFormat)->toBe(SnmpOidOutput::Module);
    expect($parser->buildOutputFlags($optionsQv))->toContain('-Oqv');

    $optionsUpperQv = $parser->parseCli(['-OQv']);
    expect($optionsUpperQv->quickPrint)->toBe(SnmpQuickPrint::Equals);
    expect($optionsUpperQv->valueOnly)->toBeTrue();
    expect($optionsUpperQv->oidFormat)->toBe(SnmpOidOutput::Module);
    expect($parser->buildOutputFlags($optionsUpperQv))->toContain('-OQv');

    $optionsVqn = $parser->parseCli(['-Ovqn']);
    expect($optionsVqn->quickPrint)->toBe(SnmpQuickPrint::NoEquals);
    expect($optionsVqn->valueOnly)->toBeTrue();
    expect($optionsVqn->oidFormat)->toBe(SnmpOidOutput::Numeric);
    expect($parser->buildOutputFlags($optionsVqn))->toContain('-Oqvn');

    $optionsQvn = $parser->parseCli(['-Oqvn']);
    expect($optionsQvn->quickPrint)->toBe(SnmpQuickPrint::NoEquals);
    expect($optionsQvn->valueOnly)->toBeTrue();
    expect($optionsQvn->oidFormat)->toBe(SnmpOidOutput::Numeric);
    expect($parser->buildOutputFlags($optionsQvn))->toContain('-Oqvn');

    $optionsNvq = $parser->parseCli(['-Onvq']);
    expect($optionsNvq->quickPrint)->toBe(SnmpQuickPrint::NoEquals);
    expect($optionsNvq->valueOnly)->toBeTrue();
    expect($optionsNvq->oidFormat)->toBe(SnmpOidOutput::Numeric);
    expect($parser->buildOutputFlags($optionsNvq))->toContain('-Oqvn');

    $optionsVqs = $parser->parseCli(['-Ovqs']);
    expect($optionsVqs->quickPrint)->toBe(SnmpQuickPrint::NoEquals);
    expect($optionsVqs->valueOnly)->toBeTrue();
    expect($optionsVqs->oidFormat)->toBe(SnmpOidOutput::Suffix);
    expect($parser->buildOutputFlags($optionsVqs))->toContain('-Oqvs');
});
