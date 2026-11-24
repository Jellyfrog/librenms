<?php

namespace App\Http\Resources\Table;

use App\Facades\LibrenmsConfig;
use App\Models\SslCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SslCertificate $resource
 */
class SslCertificateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sslCertificate = $this->resource;
        $daysWarning = (int) LibrenmsConfig::get('ssl_certificates.days_until_expiry_warning', 30);
        $daysDanger = (int) LibrenmsConfig::get('ssl_certificates.days_until_expiry_danger', 0);
        $deviceLink = null;
        if ($sslCertificate->device) {
            $deviceLink = '<a href="' . url('device/' . $sslCertificate->device_id) . '">' . e($sslCertificate->device->hostname) . '</a>';
        }

        $validTo = $sslCertificate->valid_to?->format('Y-m-d H:i');
        $status = '';
        if ($sslCertificate->disabled) {
            $status = '<span class="label label-default">' . __('Disabled') . '</span>';
        } elseif ($sslCertificate->isExpired()) {
            $status = '<span class="label label-danger">' . __('Expired') . '</span>';
        } elseif ($sslCertificate->expiresWithinDays($daysWarning)) {
            $status = '<span class="label label-warning">' . __('Expires soon') . '</span>';
        } else {
            $status = '<span class="label label-success">' . __('Valid') . '</span>';
        }

        $daysUntilExpiry = $sslCertificate->days_until_expiry;
        $daysDisplay = $daysUntilExpiry !== null
            ? (string) e($daysUntilExpiry) . ' ' . __('days')
            : '—';
        if ($daysUntilExpiry !== null && $daysUntilExpiry <= $daysDanger) {
            $daysDisplay = '<span class="text-danger">' . e($daysUntilExpiry) . ' ' . __('days') . '</span>';
        } elseif ($daysUntilExpiry !== null && $daysUntilExpiry <= $daysWarning) {
            $daysDisplay = '<span class="text-warning">' . e($daysUntilExpiry) . ' ' . __('days') . '</span>';
        }

        return [
            'id' => $sslCertificate->id,
            'host' => $sslCertificate->host,
            'port' => $sslCertificate->port,
            'subject' => $sslCertificate->subject,
            'issuer' => $sslCertificate->issuer,
            'valid_to' => $validTo,
            'days_until_expiry' => $daysDisplay,
            'last_checked_at' => $sslCertificate->last_checked_at?->format('Y-m-d H:i'),
            'device_id' => $sslCertificate->device_id,
            'device' => $deviceLink,
            'status' => $status,
            'disabled' => $sslCertificate->disabled,
        ];
    }
}
