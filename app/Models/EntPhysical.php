<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LibreNMS\Interfaces\Models\Keyable;

class EntPhysical extends DeviceRelatedModel implements Keyable
{
    use Filterable;
    use HasFactory;

    protected $table = 'entPhysical';
    protected $primaryKey = 'entPhysical_id';
    public $timestamps = false;
    protected $fillable = [
        'entPhysicalIndex',
        'entPhysicalDescr',
        'entPhysicalContainedIn',
        'entPhysicalClass',
        'entPhysicalName',
        'entPhysicalSerialNum',
        'entPhysicalModelName',
        'entPhysicalMfgName',
        'entPhysicalMfgDate',
        'entPhysicalVendorType',
        'entPhysicalParentRelPos',
        'entPhysicalHardwareRev',
        'entPhysicalFirmwareRev',
        'entPhysicalSoftwareRev',
        'entPhysicalIsFRU',
        'entPhysicalAlias',
        'entPhysicalAssetID',
        'ifIndex',
    ];

    protected array $filterable = [
        'device_id',
        'entPhysicalDescr',
        'entPhysicalName',
        'entPhysicalModelName',
        'entPhysicalSerialNum',
        'entPhysicalClass',
    ];

    /**
     * x-filter fields for the inventory table
     *
     * @return array<array<string, mixed>>
     */
    public static function filterFieldDefinitions(): array
    {
        $select = fn (string $field) => ['type' => 'select', 'endpoint' => route('ajax.select.inventory'), 'params' => ['field' => $field]];

        return [
            ['key' => 'device_id', 'label' => __('Device'), 'type' => 'select', 'endpoint' => route('ajax.select.device')],
            ['key' => 'entPhysicalDescr', 'label' => __('Description'), 'type' => 'text'],
            ['key' => 'entPhysicalName', 'label' => __('inventory.name'), ...$select('name')],
            ['key' => 'entPhysicalModelName', 'label' => __('inventory.model'), ...$select('model')],
            ['key' => 'entPhysicalClass', 'label' => __('Class'), ...$select('class')],
            ['key' => 'entPhysicalSerialNum', 'label' => __('inventory.serial'), 'type' => 'text'],
        ];
    }

    public function getCompositeKey(): int
    {
        return (int) $this->entPhysicalIndex;
    }
}
