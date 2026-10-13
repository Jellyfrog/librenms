<?php

namespace LibreNMS\Tests\Feature\Api\V1;

use ApiPlatform\Laravel\ApiPlatformProvider;
use LibreNMS\Tests\TestCase;

final class LazyLoadingTest extends TestCase
{
    public function testApiPlatformIsNotLoadedUnlessNeeded(): void
    {
        $this->assertSame([], $this->app->getProviders(ApiPlatformProvider::class), 'API Platform was loaded for a test that does not use the API');
    }
}
