<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Casts\EncryptedSecret;
use App\Models\Secret;
use LibreNMS\Exceptions\SecretDecryptionException;

beforeEach(function () {
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
});

test('round trip', function () {
    $cast = new EncryptedSecret;
    $stored = $cast->set(new Secret, 'data', ['community' => 'public'], []);

    expect($stored)->toBeString();
    $this->assertStringNotContainsString('public', $stored);
    expect($cast->get(new Secret, 'data', $stored, []))->toBe(['community' => 'public']);
});

test('empty data is stored encrypted', function () {
    $cast = new EncryptedSecret;

    foreach ([[], null] as $value) {
        $stored = $cast->set(new Secret, 'data', $value, []);
        expect($stored)->toBeString();
        expect($cast->get(new Secret, 'data', $stored, []))->toBe([]);
    }

    expect($cast->get(new Secret, 'data', null, []))->toBe([]);
});

test('decrypts once for each model until the value changes', function () {
    $secret = new Secret(['data' => ['community' => 'public']]);
    $decrypts = countDecrypts();

    expect($secret->data)->toBe(['community' => 'public']);
    expect($secret->data)->toBe(['community' => 'public']);
    expect($decrypts->count)->toBe(1);

    // another model with the same payload decrypts its own
    $other = (new Secret)->setRawAttributes(['data' => $secret->getAttributes()['data']]);
    expect($other->data)->toBe(['community' => 'public']);
    expect($decrypts->count)->toBe(2);

    $secret->data = ['community' => 'private'];
    expect($secret->data)->toBe(['community' => 'private']);
    expect($secret->data)->toBe(['community' => 'private']);
    expect($decrypts->count)->toBe(3);
});

test('invalid payload throws', function () {
    $this->expectException(SecretDecryptionException::class);

    (new EncryptedSecret)->get(new Secret, 'data', 'invalid-encrypted-payload', []);
});

function countDecrypts(): object
{
    $counter = new class
    {
    };
    $encrypter = app('encrypter');
    $spy = Mockery::mock($encrypter);
    $spy->shouldReceive('decrypt')->andReturnUsing(function (...$args) use ($encrypter, $counter) {
        $counter->count++;

        return $encrypter->decrypt(...$args);
    });
    $this->app->instance('encrypter', $spy);

    return $counter;
}
