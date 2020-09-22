<?php

namespace App\Models;

/**
 * App\Models\Processor
 *
 * @property int $processor_id
 * @property int $entPhysicalIndex
 * @property int|null $hrDeviceIndex
 * @property int $device_id
 * @property string $processor_oid
 * @property string $processor_index
 * @property string $processor_type
 * @property int $processor_usage
 * @property string $processor_descr
 * @property int $processor_precision
 * @property int|null $processor_perc_warn
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereEntPhysicalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereHrDeviceIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorPercWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorPrecision($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Processor whereProcessorUsage($value)
 * @mixin \Eloquent
 */
class Processor extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'processor_id';

    // ---- Helper Functions ----

    /**
     * Return Processor Description, formatted for display
     *
     * @return string
     */
    public function getFormattedDescription()
    {
        $bad_descr = [
            'GenuineIntel:',
            'AuthenticAMD:',
            'Intel(R)',
            'CPU',
            '(R)',
            '(tm)',
        ];

        $descr = str_replace($bad_descr, '', $this->processor_descr);

        // reduce extra spaces
        $descr = str_replace('  ', ' ', $descr);

        return $descr;
    }
}
