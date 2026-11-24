<?php

namespace App\Http\Controllers;

use App\Facades\LibrenmsConfig;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Location::class);

        $data = [
            'maps_config' => [
                'engine' => LibrenmsConfig::get('geoloc.engine'),
                'api_key' => LibrenmsConfig::get('geoloc.api_key'),
                'tile_url' => LibrenmsConfig::get('leaflet.tile_url', '{s}.tile.openstreetmap.org'),
            ],
        ];

        return view('locations', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Location  $location
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Location $location)
    {
        $this->authorize('update', $location);

        $this->validate($request, [
            'lat' => 'required|numeric|max:90|min:-90',
            'lng' => 'required|numeric|max:180|min:-180',
        ]);

        $location->fill($request->only(['lat', 'lng']));
        $location->fixed_coordinates = true;  // user has set coordinates, block automated changes
        $location->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Location  $location
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function destroy(Request $request, Location $location)
    {
        $this->authorize('delete', $location);

        $location->delete();

        return response()->json(['status' => 'success']);
    }
}
