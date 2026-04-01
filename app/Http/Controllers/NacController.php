<?php

namespace App\Http\Controllers;

class NacController extends Controller
{
    /** @return mixed */
    public function index()
    {
        $data = [];

        return view('nac', $data);
    }
}
