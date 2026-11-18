<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

final class AuthLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthLogRendersLocalDataTable(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create(['enabled' => 1])->assignRole('admin');
        AuthLog::factory()->create(['user' => 'dt-user', 'address' => '192.0.2.10', 'result' => 'Logged In']);
        AuthLog::factory()->create(['user' => '<script>alert(1)</script>', 'address' => '192.0.2.11', 'result' => 'Authentication Failure']);

        $this->actingAs($admin)->get('/authlog')
            ->assertOk()
            ->assertSee('dataTable(', false)
            ->assertSee('\u0022mode\u0022:\u0022local\u0022', false)
            ->assertSee('dt-user')
            ->assertSee('192.0.2.10')
            ->assertDontSee('bootgrid(', false)
            // rows are json in an attribute and rendered with x-text, never raw html
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
