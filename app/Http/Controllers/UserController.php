<?php
/**
 * UserController.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuthLog;
use App\Models\Dashboard;
use App\Models\User;
use App\Models\UserPref;
use Auth;
use Flasher\Prime\FlasherInterface;
use Illuminate\Support\Str;
use LibreNMS\Authentication\LegacyAuth;
use LibreNMS\Config;
use URL;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('deny-demo');
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(): View
    {
        $this->authorize('manage', User::class);

        return view('user.index', [
            'users' => User::with('preferences')->orderBy('username')->get(),
            'multiauth' => User::query()->distinct('auth_type')->count('auth_type') > 1,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $tmp_user = new User;
        $tmp_user->can_modify_passwd = (int) LegacyAuth::get()->canUpdatePasswords(); // default to true for new users

        return view('user.create', [
            'user' => $tmp_user,
            'dashboard' => null,
            'dashboards' => Dashboard::allAvailable($tmp_user)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, FlasherInterface $flasher): RedirectResponse
    {
        $user = $request->only(['username', 'realname', 'email', 'descr', 'level', 'can_modify_passwd']);
        $user['auth_type'] = LegacyAuth::getType();
        $user['can_modify_passwd'] = $request->get('can_modify_passwd'); // checkboxes are missing when unchecked

        $user = User::create($user);

        $user->setPassword($request->new_password);
        $user->auth_id = (string) LegacyAuth::get()->getUserid($user->username) ?: $user->user_id;
        $this->updateDashboard($user, $request->get('dashboard'));

        if ($user->save()) {
            $flasher->addSuccess(__('User :username created', ['username' => $user->username]));

            return redirect(route('users.index'));
        }

        $flasher->addError(__('Failed to create user'));

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @return string
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return $user->username;
    }

    /**
     * Show the form for editing the specified resource.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $data = [
            'user' => $user,
            'dashboard' => UserPref::getPref($user, 'dashboard'),
            'dashboards' => Dashboard::allAvailable($user)->get(),
        ];

        if (Config::get('twofactor')) {
            $lockout_time = Config::get('twofactor_lock');
            $twofactor = UserPref::getPref($user, 'twofactor');
            $data['twofactor_enabled'] = isset($twofactor['key']);

            // if enabled and 3 or more failures
            $last_failure = isset($twofactor['last']) ? (time() - $twofactor['last']) : 0;
            $data['twofactor_locked'] = isset($twofactor['fails']) && $twofactor['fails'] >= 3 && (! $lockout_time || $last_failure < $lockout_time);
        }

        return view('user.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user, FlasherInterface $flasher): RedirectResponse
    {
        if ($request->get('new_password') && $user->canSetPassword($request->user())) {
            $user->setPassword($request->new_password);
            /** @var User $current_user */
            $current_user = Auth::user();
            Auth::setUser($user); // make sure new password is loaded, can only logout other sessions for the active user
            Auth::logoutOtherDevices($request->new_password);

            // when setting the password on another account, restore back to the user's account.
            if ($current_user->user_id !== $user->user_id) {
                Auth::setUser($current_user);
            }
        }

        $user->fill($request->validated());

        if ($request->has('dashboard') && $this->updateDashboard($user, $request->get('dashboard'))) {
            $flasher->addSuccess(__('Updated dashboard for :username', ['username' => $user->username]));
        }

        if ($user->save()) {
            $flasher->addSuccess(__('User :username updated', ['username' => $user->username]));

            return redirect(route(Str::contains(URL::previous(), 'preferences') ? 'preferences.index' : 'users.index'));
        }

        $flasher->addError(__('Failed to update user :username', ['username' => $user->username]));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(__('User :username deleted.', ['username' => $user->username]));
    }

    /**
     * @param  mixed  $dashboard
     */
    protected function updateDashboard(User $user, $dashboard): bool
    {
        if ($dashboard) {
            $existing = UserPref::getPref($user, 'dashboard');
            if ($dashboard != $existing) {
                UserPref::setPref($user, 'dashboard', $dashboard);

                return true;
            }
        }

        return false;
    }

    public function authlog(): View
    {
        $this->authorize('manage', User::class);

        return view('user.authlog', [
            'authlog' => AuthLog::orderBy('datetime', 'DESC')->get(),
        ]);
    }
}
