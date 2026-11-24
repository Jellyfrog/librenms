<?php

namespace App\Http\Controllers;

use App\Models\PortSecurity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortSecurityController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(PortSecurity::filterValidationRules());

        return view('port-security.index', [
            'pagetitle' => __('Port Security'),
            'filterFields' => PortSecurity::filterFieldDefinitions(),
            'filter' => $request->array('filter'),
        ]);
    }
}
