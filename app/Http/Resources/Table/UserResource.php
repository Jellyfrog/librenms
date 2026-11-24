<?php

namespace App\Http\Resources\Table;

use App\Models\UserPref;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * User row of the user management table, expects preferences and roles to be loaded
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->username,
            'realname' => $this->realname,
            'roles' => $this->roles->map(fn ($role) => Str::title(str_replace('-', ' ', $role->name)))->values(),
            'auth_type' => $this->auth_type,
            'email' => $this->email,
            'phone' => $this->phone,
            'timezone' => UserPref::getPref($this->resource, 'timezone') ?: __('Browser Timezone'),
            'enabled' => (bool) $this->enabled,
            'twofactor' => (bool) UserPref::getPref($this->resource, 'twofactor'),
            'descr' => $this->descr,
        ];
    }
}
