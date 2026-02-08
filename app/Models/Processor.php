<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;

#[ApiResource(
    shortName: 'Processor',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'order', filter: new OrderFilter())]
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
