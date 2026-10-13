<?php

namespace LibreNMS\Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Artisan;

final class OpenApiExportTest extends ApiV1TestCase
{
    private const FILE = 'doc/API/v1-openapi.json';

    public function testCommittedDescriptionIsCurrent(): void
    {
        Artisan::call('api:openapi:export');

        $this->assertJsonStringEqualsJsonFile(
            base_path(self::FILE),
            Artisan::output(),
            'The API changed. Regenerate the reference with: lnms api:openapi:export -o ' . self::FILE,
        );
    }
}
