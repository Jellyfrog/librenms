<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\ComposerHelper;

test('get plugins is independent of the working directory', function () {
    $previous = getcwd();

    // Establish the expected answer from the install directory itself.
    chdir(base_path());
    $expected = ComposerHelper::getPlugins();

    $decoy = sys_get_temp_dir() . '/librenms-composer-plugins-' . getmypid();
    @mkdir($decoy, 0755, true);
    file_put_contents(
        $decoy . '/composer.plugins.json',
        '{"require":{"decoy/decoy":"^1.0"}}'
    );

    try {
        chdir($decoy);

        expect(ComposerHelper::getPlugins())->toBe($expected, 'getPlugins() must resolve composer.plugins.json against the install directory');
        $this->assertArrayNotHasKey(
            'decoy/decoy',
            ComposerHelper::getPlugins(),
            'getPlugins() must not read a composer.plugins.json from the working directory'
        );
    } finally {
        if ($previous !== false) {
            chdir($previous);
        }
        @unlink($decoy . '/composer.plugins.json');
        @rmdir($decoy);
    }
});

test('plugins file can be set in the environment', function () {
    $default = ComposerHelper::getPluginsFile();
    expect($default)->toBe(base_path('composer.plugins.json'));

    $file = sys_get_temp_dir() . '/librenms-composer-plugins-' . getmypid() . '.json';
    file_put_contents($file, '{"require":{"custom/package":"^1.0"}}');

    try {
        putenv("LIBRENMS_COMPOSER_PLUGINS=$file");

        expect(ComposerHelper::getPluginsFile())->toBe($file);
        expect(ComposerHelper::getPlugins())->toBe(['custom/package' => '^1.0']);
    } finally {
        putenv('LIBRENMS_COMPOSER_PLUGINS');
        @unlink($file);
    }

    expect(ComposerHelper::getPluginsFile())->toBe($default);
});
