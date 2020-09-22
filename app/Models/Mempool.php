<?php

namespace App\Models;

/**
 * App\Models\Mempool
 *
 * @property int $mempool_id
 * @property string $mempool_index
 * @property int|null $entPhysicalIndex
 * @property int|null $hrDeviceIndex
 * @property string $mempool_type
 * @property int $mempool_precision
 * @property string $mempool_descr
 * @property int $device_id
 * @property int $mempool_perc
 * @property int $mempool_used
 * @property int $mempool_free
 * @property int $mempool_total
 * @property int|null $mempool_largestfree
 * @property int|null $mempool_lowestfree
 * @property int $mempool_deleted
 * @property int|null $mempool_perc_warn
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereEntPhysicalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereHrDeviceIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolFree($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolLargestfree($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolLowestfree($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolPerc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolPercWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolPrecision($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mempool whereMempoolUsed($value)
 * @mixin \Eloquent
 */
class Mempool extends DeviceRelatedModel
{
    protected $table = 'mempools';

    protected $primaryKey = 'mempool_id';
}
