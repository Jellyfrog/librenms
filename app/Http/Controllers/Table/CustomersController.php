<?php

namespace App\Http\Controllers\Table;

use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\CustomerResource;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

/**
 * Customers are the descriptions of ports with a customer type (customers_descr), one row per customer with its ports
 */
class CustomersController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Port::class);
        $types = Arr::wrap(LibrenmsConfig::get('customers_descr', ['cust']));

        $query = Port::hasAccess($request->user())
            ->select('port_descr_descr')
            ->whereIn('port_descr_type', $types)
            ->groupBy('port_descr_descr');
        $request->searchQuery($query, ['port_descr_descr', 'ifName', 'ifDescr', 'ifAlias', 'port_descr_speed', 'port_descr_notes', 'device.hostname', 'device.sysDescr']);
        $request->sortQuery($query, ['port_descr_descr'], ['port_descr_descr' => 'asc']);

        $customers = $request->paginate($query);
        $ports = Port::hasAccess($request->user())
            ->with('device')
            ->whereIn('port_descr_type', $types)
            ->whereIn('port_descr_descr', $customers->pluck('port_descr_descr'))
            ->get()
            ->groupBy('port_descr_descr');

        return CustomerResource::collection($customers->through(fn (Port $customer) => new CustomerResource([
            'name' => $customer->port_descr_descr,
            'ports' => $ports->get($customer->port_descr_descr, collect()),
        ])));
    }
}
