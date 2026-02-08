<?php

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(
    shortName: 'IpsecTunnel',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
class IpsecTunnel extends Model
{
    protected $table = 'ipsec_tunnels';
    protected $primaryKey = 'tunnel_id';
    public $timestamps = false;
}
