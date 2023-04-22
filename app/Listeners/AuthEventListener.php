<?php

namespace App\Listeners;

use App\Models\User;
use DB;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Request;

class AuthEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the login event.
     */
    public function login(Login $event): void
    {
        /** @var User $user */
        $user = $event->user ?: (object) ['username' => 'Not found'];

        DB::table('authlog')->insert(['user' => $user->username ?: '', 'address' => Request::ip(), 'result' => 'Logged In']);

        flash()->addInfo('Welcome ' . ($user->realname ?: $user->username));
    }

    /**
     * Handle the logout event.
     */
    public function logout(Logout $event): void
    {
        /** @var User $user */
        $user = $event->user ?: (object) ['username' => 'Not found'];

        DB::table('authlog')->insert(['user' => $user->username ?: '', 'address' => Request::ip(), 'result' => 'Logged Out']);
    }
}
