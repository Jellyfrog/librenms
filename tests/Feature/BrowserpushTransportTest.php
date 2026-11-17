<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\User;
use LibreNMS\Alert\Transport\Browserpush;

test('config template lists users with duplicate realnames', function () {
    $admin = User::factory()->create(['username' => 'richard', 'realname' => 'Richard Smith']);
    $readonly = User::factory()->create(['username' => 'richard-ro', 'realname' => 'Richard Smith']);
    $solo = User::factory()->create(['username' => 'solo', 'realname' => 'Solo User']);
    $noRealname = User::factory()->create(['username' => 'plainname', 'realname' => '']);

    $options = Browserpush::configTemplate()['config'][0]['options'];

    expect($options['All Users'])->toBe(0);

    // both users sharing a realname are listed, disambiguated by username
    expect($options['Richard Smith (richard)'])->toBe($admin->user_id);
    expect($options['Richard Smith (richard-ro)'])->toBe($readonly->user_id);

    // a unique realname is shown as-is, and the username is used when no realname is set
    expect($options['Solo User'])->toBe($solo->user_id);
    expect($options['plainname'])->toBe($noRealname->user_id);
});

test('config template keeps numeric username label', function () {
    $numeric = User::factory()->create(['username' => '1001', 'realname' => '']);

    $options = Browserpush::configTemplate()['config'][0]['options'];

    expect($options['All Users'])->toBe(0);

    // PHP casts a numeric-string key to int and array_merge renumbers
    // integer keys, so a plain merge would lose the '1001' label
    expect($options['1001'])->toBe($numeric->user_id);
});
