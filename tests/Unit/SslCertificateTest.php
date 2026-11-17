<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\SslCertificate;
use Illuminate\Support\Carbon;

beforeEach(function () {
    LibrenmsConfig::set('ssl_certificates.days_until_expiry_warning', 15);
    LibrenmsConfig::set('ssl_certificates.days_until_expiry_danger', 0);
});

function makeCertificate(Carbon $originalValidTo, Carbon $newValidTo, int $originalDays, int $newDays): SslCertificate
{
    $certificate = new SslCertificate;
    $certificate->forceFill([
        'host' => 'example.com',
        'port' => 443,
        'valid_to' => $originalValidTo,
        'days_until_expiry' => $originalDays,
    ]);
    $certificate->syncOriginal();

    $certificate->valid_to = $newValidTo;
    $certificate->days_until_expiry = $newDays;

    return $certificate;
}

test('daily decrement is not tracked', function () {
    $cases = [
        [112, 111],
        [31, 30],
        [30, 29],
    ];

    foreach ($cases as [$originalDays, $newDays]) {
        $validTo = Carbon::now()->addDays($newDays);
        $certificate = makeCertificate($validTo, $validTo, $originalDays, $newDays);

        expect($certificate->getTrackedChanges())->toBe('', "days_until_expiry {$originalDays} → {$newDays}");
    }
});

test('change within warning window is not tracked', function () {
    $validTo = Carbon::now()->addDays(14);
    $certificate = makeCertificate($validTo, $validTo, 15, 14);

    expect($certificate->getTrackedChanges())->toBe('');
});

test('entering warning window is tracked', function () {
    $validTo = Carbon::now()->addDays(15);
    $certificate = makeCertificate($validTo, $validTo, 16, 15);

    expect($certificate->getTrackedChanges())->toBe('days_until_expiry (16 → 15)');
});

test('stored crossing before live warning boundary is not tracked', function () {
    $validTo = Carbon::now()->addDays(15)->addMinutes(30);
    $certificate = makeCertificate($validTo, $validTo, 16, 15);

    expect($certificate->getTrackedChanges())->toBe('');
});

test('expiry is tracked', function () {
    $validTo = Carbon::now();
    $certificate = makeCertificate($validTo, $validTo, 1, 0);

    expect($certificate->getTrackedChanges())->toBe('days_until_expiry (1 → 0)');

    $certificate = makeCertificate($validTo, $validTo, 0, -1);

    expect($certificate->getTrackedChanges())->toBe('');
});

test('renewal tracks valid to but not days', function () {
    $originalValidTo = Carbon::now()->subDays(3);
    $newValidTo = Carbon::now()->addDays(360);

    $certificate = makeCertificate($originalValidTo, $newValidTo, -3, 360);

    $changes = $certificate->getTrackedChanges();

    $this->assertStringContainsString('valid_to', $changes);
    $this->assertStringNotContainsString('days_until_expiry', $changes);
});

test('is within warning window', function () {
    $daysUntilExpiryToExpectations = [
        [16, false],
        [15, true],
        [1, true],
        [0, true],
        [-1, true],
    ];

    foreach ($daysUntilExpiryToExpectations as [$days, $expected]) {
        $certificate = new SslCertificate;
        $certificate->valid_to = Carbon::now()->addDays($days);

        expect($certificate->isWithinWarningWindow())->toBe($expected, "isWithinWarningWindow() with valid_to {$days} days from now");
    }

    $certificate = new SslCertificate;
    $certificate->valid_to = null;

    expect($certificate->isWithinWarningWindow())->toBeFalse();
});
