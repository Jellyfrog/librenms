<?php

namespace App\Http\Controllers\Table;

use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\LocationResource;
use App\Models\Device;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocationController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        // graphs are added to the page after it loaded
        LibrenmsConfig::set('enable_lazy_load', false);

        return LocationResource::collection($request->paginate($this->query($request)));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $request->exportRows($this->query($request))->map(fn (Location $location) => [
            $location->id,
            $location->location,
            $location->lat,
            $location->lng,
            $location->devices_count,
            $location->getAttribute('down_count'),
        ]);

        return $this->streamCsv('locations', [__('ID'), __('Location'), __('Latitude'), __('Longitude'), __('Devices'), __('Down')], $rows);
    }

    /**
     * @return Builder<Location>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Location::class);

        $query = Location::hasAccess($request->user())->withCount([
            'devices',
            'devices as down_count' => fn ($q) => (new Device)->scopeIsDown($q),
        ]);
        $request->searchQuery($query, ['location']);

        return $request->sortQuery($query, ['location', 'devices' => 'devices_count', 'down' => 'down_count'], ['location' => 'asc']);
    }
}
