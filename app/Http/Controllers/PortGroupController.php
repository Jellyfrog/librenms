<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use App\Models\PortGroup;
use Flasher\Prime\FlasherInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('port-group.index', [
            'port_groups' => PortGroup::orderBy('name')->withCount('ports')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('port-group.create', [
            'port_group' => new PortGroup(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, FlasherInterface $flasher): RedirectResponse
    {
        $this->validate($request, [
            'name' => 'required|string|unique:port_groups',
        ]);

        $portGroup = PortGroup::make($request->only(['name', 'desc']));
        $portGroup->save();

        $flasher->addSuccess(__('Port Group :name created', ['name' => $portGroup->name]));

        return redirect()->route('port-groups.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PortGroup $portGroup): View
    {
        return view('port-group.edit', [
            'port_group' => $portGroup,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PortGroup $portGroup, FlasherInterface $flasher): RedirectResponse
    {
        $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('port_groups', 'name')->where(function ($query) use ($portGroup) {
                    $query->where('id', '!=', $portGroup->id);
                }),
            ],
            'desc' => 'string|max:255',
        ]);

        $portGroup->fill($request->only(['name', 'desc']));

        if ($portGroup->save()) {
            $flasher->addSuccess(__('Port Group :name updated', ['name' => $portGroup->name]));
        } else {
            $flasher->addError(__('Failed to save'));

            return redirect()->back()->withInput();
        }

        return redirect()->route('port-groups.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PortGroup $portGroup): Response
    {
        $portGroup->delete();

        $msg = __('Port Group :name deleted', ['name' => htmlentities($portGroup->name)]);

        return response($msg, 200);
    }
}
