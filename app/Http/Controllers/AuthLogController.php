<?php

namespace App\Http\Controllers;

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
            // the table sorts, searches and paginates these in the browser, it sorts by datetime desc
            'rows' => AuthLog::get()->map(fn (AuthLog $log) => [
                'id' => $log->id,
                'datetime' => $log->datetime?->format('Y-m-d H:i:s'),
                'user' => $log->user,
                'address' => $log->address,
                'result' => $log->result,
            ]),
        ]);
    }
}
