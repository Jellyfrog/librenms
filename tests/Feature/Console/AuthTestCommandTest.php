<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\User;
use LibreNMS\Authentication\LegacyAuth;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('mysql authentication still displays account', function () {
    LibrenmsConfig::set('auth_mechanism', 'mysql');
    LegacyAuth::reset();
    $user = User::factory()->create(['username' => 'alice', 'auth_type' => 'mysql', 'enabled' => 1]);

    $this->artisan('auth:test', ['username' => 'alice'])
        ->expectsQuestion('Password', 'password')
        ->expectsOutput('User (' . $user->user_id . '):')
        ->expectsOutput('AUTH SUCCESS')
        ->doesntExpectOutputToContain('remember_token')
        ->doesntExpectOutputToContain('password =>')
        ->assertExitCode(0);
});

test('mysql authentication failure returns failure exit code', function () {
    LibrenmsConfig::set('auth_mechanism', 'mysql');
    LegacyAuth::reset();
    User::factory()->create(['username' => 'alice', 'auth_type' => 'mysql', 'enabled' => 1]);

    $this->artisan('auth:test', ['username' => 'alice'])
        ->expectsQuestion('Password', 'wrong')
        ->expectsOutputToContain('Error:')
        ->expectsOutput('AUTH FAILURE')
        ->doesntExpectOutputToContain('wrong')
        ->assertExitCode(1);
});

test('empty password fails without authenticating', function () {
    $this->artisan('auth:test', ['username' => 'alice'])
        ->expectsQuestion('Password', '')
        ->expectsOutput('A password is required.')
        ->assertExitCode(1);
});

test('noninteractive execution fails without prompting', function () {
    $this->artisan('auth:test', ['username' => 'alice', '--no-interaction' => true])
        ->expectsOutput('An interactive terminal is required to enter the password.')
        ->assertExitCode(1);
});