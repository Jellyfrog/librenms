<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\LibrenmsConfig;
use Illuminate\Encryption\Encrypter;

test('rotates without interaction', function () {
    $cipher = config('app.cipher');
    $oldKey = Encrypter::generateKey($cipher);
    $newKey = Encrypter::generateKey($cipher);
    config(['app.key' => 'base64:' . base64_encode($newKey)]);

    LibrenmsConfig::persist('validation.encryption.test', new Encrypter($oldKey, $cipher)->encryptString('valid'));

    $this->artisan('key:rotate', ['old_key' => 'base64:' . base64_encode($oldKey), '--no-interaction' => true])
        ->assertExitCode(0);

    expect(new Encrypter($newKey, $cipher)->decryptString(LibrenmsConfig::get('validation.encryption.test')))->toBe('valid');
});
