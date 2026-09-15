<?php

// shaped like composer's vendor/composer/installed.php: replaces and provides are already
// resolved, so "self.version" never appears here
return [
    'root' => ['name' => 'librenms/librenms', 'pretty_version' => 'dev-master'],
    'versions' => [
        'illuminate/console' => ['replaced' => ['v12.10.1']],
        'illuminate/support' => ['pretty_version' => 'v9.0.0'],
        'laravel/framework' => ['pretty_version' => 'v12.10.1'],
        'librenms/laravel-vue-i18n-generator' => ['pretty_version' => 'dev-master'],
        'librenms/librenms' => ['pretty_version' => 'dev-master'],
        'librenms/plugin-interfaces' => ['pretty_version' => '1.2.0'],
        'psr/log' => ['pretty_version' => '3.0.2'],
        'psr/log-implementation' => ['provided' => ['3.0.0']],
        'spatie/once' => ['replaced' => ['*']],
    ],
];
