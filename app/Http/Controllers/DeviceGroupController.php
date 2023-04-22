<?php

namespace App\Http\Controllers;

use App\Models\DeviceGroup;
use Flasher\Prime\FlasherInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LibreNMS\Alerting\QueryBuilderFilter;
use LibreNMS\Alerting\QueryBuilderFluentParser;

class DeviceGroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DeviceGroup::class, 'device_group');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('manage', DeviceGroup::class);

        return view('device-group.index', [
            'device_groups' => DeviceGroup::orderBy('name')->withCount('devices')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('device-group.create', [
            'device_group' => new DeviceGroup(),
            'filters' => json_encode(new QueryBuilderFilter('group')),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, FlasherInterface $flasher): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|string|unique:device_groups',
            'type' => 'required|in:dynamic,static',
            'devices' => 'array|required_if:type,static',
            'devices.*' => 'integer',
            'rules' => 'json|required_if:type,dynamic',
        ]);

        $deviceGroup = DeviceGroup::make($request->only(['name', 'desc', 'type']));
        $deviceGroup->rules = json_decode($request->rules);
        $deviceGroup->save();

        if ($request->type == 'static') {
            $deviceGroup->devices()->sync($request->devices);
        }

        $flasher->addSuccess(__('Device Group :name created', ['name' => $deviceGroup->name]));

        return redirect()->route('device-groups.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(DeviceGroup $deviceGroup): RedirectResponse
    {
        return redirect(url('/devices/group=' . $deviceGroup->id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeviceGroup $deviceGroup): View
    {
        // convert old rules on edit
        if (is_null($deviceGroup->rules)) {
            $query_builder = QueryBuilderFluentParser::fromOld($deviceGroup->pattern);
            $deviceGroup->rules = $query_builder->toArray();
        }

        return view('device-group.edit', [
            'device_group' => $deviceGroup,
            'filters' => json_encode(new QueryBuilderFilter('group')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DeviceGroup $deviceGroup, FlasherInterface $flasher): RedirectResponse
    {
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                Rule::unique('device_groups')->where(function ($query) use ($deviceGroup) {
                    $query->where('id', '!=', $deviceGroup->id);
                }),
            ],
            'type' => 'required|in:dynamic,static',
            'devices' => 'array|required_if:type,static',
            'devices.*' => 'integer',
            'rules' => 'json|required_if:type,dynamic',
        ]);

        $deviceGroup->fill($request->only(['name', 'desc', 'type']));

        $devices_updated = false;
        if ($deviceGroup->type == 'static') {
            // sync device_ids from input
            $updated = $deviceGroup->devices()->sync($request->get('devices', []));
            // check for attached/detached/updated
            $devices_updated = array_sum(array_map(function ($device_ids) {
                return count($device_ids);
            }, $updated)) > 0;
        } else {
            $deviceGroup->rules = json_decode($request->rules);
        }

        if ($deviceGroup->isDirty() || $devices_updated) {
            try {
                if ($deviceGroup->save() || $devices_updated) {
                    $flasher->addSuccess(__('Device Group :name updated', ['name' => $deviceGroup->name]));
                } else {
                    $flasher->addError(__('Failed to save'));

                    return redirect()->back()->withInput();
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return redirect()->back()->withInput()->withErrors([
                    'rules' => __('Rules resulted in invalid query: ') . $e->getMessage(),
                ]);
            }
        } else {
            $flasher->addInfo(__('No changes made'));
        }

        return redirect()->route('device-groups.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeviceGroup $deviceGroup): Response
    {
        if ($deviceGroup->serviceTemplates()->exists()) {
            $msg = __('Device Group :name still has Service Templates associated with it. Please remove or update the Service Template accordingly', ['name' => $deviceGroup->name]);

            return response($msg, 200);
        }
        $deviceGroup->delete();

        $msg = __('Device Group :name deleted', ['name' => htmlentities($deviceGroup->name)]);

        return response($msg, 200);
    }
}
