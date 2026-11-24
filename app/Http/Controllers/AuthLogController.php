<?php

namespace App\Http\Controllers;

use App\Http\Resources\Table\AuthLogResource;
use App\Models\AuthLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class AuthLogController
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('auth-log.view');

        return view('user.authlog', [
            // the table sorts, searches and paginates these in the browser
            'rows' => AuthLogResource::collection(AuthLog::orderBy('datetime', 'desc')->get())->resolve(),
        ]);
    }
}
