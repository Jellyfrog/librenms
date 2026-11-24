<?php

namespace App\Http\Controllers;

use App\Models\EntPhysical;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', EntPhysical::class);

        $request->validate([
            'device' => 'nullable|int',
            'descr' => 'nullable|string',
            'model' => 'nullable|string',
            'serial' => 'nullable|string',
            ...EntPhysical::filterValidationRules(),
        ]);

        return view('inventory', [
            // old links use ?device=, ?descr=, ?model= and ?serial=
            'filter' => EntPhysical::filtersFromRequest($request, [
                'device' => 'device_id',
                'descr' => ['entPhysicalDescr', 'contains'],
                'model' => 'entPhysicalModelName',
                'serial' => ['entPhysicalSerialNum', 'contains'],
            ]),
            'filterFields' => EntPhysical::filterFieldDefinitions(),
            'show_purge' => Gate::allows('purge', EntPhysical::class) && EntPhysical::whereDoesntHave('device')->exists(),
        ]);
    }

    public function purge()
    {
        $this->authorize('purge', EntPhysical::class);

        EntPhysical::whereDoesntHave('device')->delete();

        return redirect()->back();
    }
}
