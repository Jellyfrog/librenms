<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. Every test file in this suite declares the base class it needs via a
| file-level uses() call, for example: uses(LibreNMS\Tests\TestCase::class).
|
| The available base classes are:
|   - LibreNMS\Tests\TestCase           boots the Laravel application
|   - LibreNMS\Tests\DBTestCase         additionally requires a database (DBTEST=1)
|   - LibreNMS\Tests\InMemoryDbTestCase migrates an in-memory sqlite database
|   - LibreNMS\Tests\Feature\SnmpTraps\SnmpTrapTestCase provides assertTrapLogsMessage()
|
| Browser tests in tests/Browser are Laravel Dusk class-based tests executed by
| `php artisan dusk`, not by Pest.
|
*/
