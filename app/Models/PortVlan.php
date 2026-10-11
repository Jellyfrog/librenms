<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LibreNMS\Interfaces\Models\Keyable;

class PortVlan extends PortRelatedModel implements Keyable
{
    use HasFactory;
    protected $table = 'ports_vlans';
    protected $primaryKey = 'port_vlan_id';
    public $timestamps = false;
    protected $fillable = [
        'port_vlan_id',
        'device_id',
        'port_id',
        'vlan',
        'baseport',
        'priority',
        'state',
        'cost',
        'untagged',
    ];

    // ---- Define Relationships ----

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    /**
     * ports_vlans has no vlan_id, it is matched on vlan number within the same device.
     * Works for lazy loading and has/count queries, but can not be eager loaded.
     * Not named vlan() as that would be shadowed by the vlan column.
     *
     * @return BelongsTo<Vlan, $this>
     */
    public function deviceVlan(): BelongsTo
    {
        $relation = $this->belongsTo(Vlan::class, 'vlan', 'vlan_vlan');

        return $this->device_id === null
            ? $relation->whereColumn('vlans.device_id', 'ports_vlans.device_id')
            : $relation->where('vlans.device_id', $this->device_id);
    }

    // ---- Accessors/Mutators ----

    public function getUntaggedAttribute($value)
    {
        if (! $value) {
            if ($this->port && $this->vlan == $this->port->ifVlan) {
                $value = 1;
            }
        }

        return $value;
    }

    public function getCompositeKey(): string
    {
        return $this->port_id . '-' . $this->vlan;
    }
}
