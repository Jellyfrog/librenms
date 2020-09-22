<?php

namespace App\Models;

/**
 * App\Models\EntPhysical
 *
 * @property int $entPhysical_id
 * @property int $device_id
 * @property int $entPhysicalIndex
 * @property string $entPhysicalDescr
 * @property string $entPhysicalClass
 * @property string $entPhysicalName
 * @property string|null $entPhysicalHardwareRev
 * @property string|null $entPhysicalFirmwareRev
 * @property string|null $entPhysicalSoftwareRev
 * @property string|null $entPhysicalAlias
 * @property string|null $entPhysicalAssetID
 * @property string|null $entPhysicalIsFRU
 * @property string $entPhysicalModelName
 * @property string|null $entPhysicalVendorType
 * @property string $entPhysicalSerialNum
 * @property int $entPhysicalContainedIn
 * @property int $entPhysicalParentRelPos
 * @property string $entPhysicalMfgName
 * @property int|null $ifIndex
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalAssetID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalContainedIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalFirmwareRev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalHardwareRev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalIsFRU($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalMfgName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalParentRelPos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalSerialNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalSoftwareRev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereEntPhysicalVendorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EntPhysical whereIfIndex($value)
 * @mixin \Eloquent
 */
class EntPhysical extends DeviceRelatedModel
{
    protected $table = 'entPhysical';

    protected $primaryKey = 'entPhysical_id';
}
