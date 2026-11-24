@extends('layouts.librenmsv1')

@section('title', __('Manage Users'))

@section('content')
<div class="container-fluid">
    <x-data-table name="users" mode="local" :rows="$rows" row-key="user_id" :sort="['username' => 'asc']" :title="__('Manage Users')">
        <x-slot:heading>
            @can('create', \App\Models\User::class)
                <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('Add User') }}</a>
            @endcan
            @can('viewAny', \Spatie\Permission\Models\Role::class)
                <a href="{{ route('roles.index') }}" class="btn btn-default">{{ __('permissions.rbac.manage_roles') }}</a>
            @endcan
        </x-slot:heading>
        <x-data-table.column field="user_id" :label="__('ID')" :visible="false" />
        <x-data-table.column field="username" :label="__('Username')" :hideable="false" />
        <x-data-table.column field="realname" :label="__('Real Name')" />
        <x-data-table.column field="roles" :label="__('Roles')" :sortable="false">
            <template x-for="role in row.roles" :key="role"><span class="label label-info tw:mr-1" x-text="role"></span></template>
        </x-data-table.column>
        <x-data-table.column field="auth_type" :label="__('auth.title')" :visible="$multiauth" />
        <x-data-table.column field="email" :label="__('Email')" />
        <x-data-table.column field="phone" :label="__('Phone')" />
        <x-data-table.column field="timezone" :label="__('Timezone')" />
        @if(\LibreNMS\Authentication\LegacyAuth::getType() == 'mysql')
            <x-data-table.column field="enabled" :label="__('Enabled')">
                <span class="fa fa-fw" :class="row.enabled ? 'fa-check text-success' : 'fa-close text-danger'"></span>
            </x-data-table.column>
        @endif
        @config('twofactor')
            <x-data-table.column field="twofactor" :label="__('2FA')">
                <span x-show="row.twofactor" class="fa fa-fw fa-check text-success"></span>
            </x-data-table.column>
        @endconfig
        <x-data-table.column field="descr" :label="__('Description')" />
        <x-data-table.column field="action" :label="__('Actions')" :sortable="false" :searchable="false" class="tw:whitespace-nowrap">
            <a :href="route('users.permissions.edit', row.user_id)" title="{{ __('Manage Access') }}" class="btn btn-sm btn-primary"><i class="fa fa-tasks"></i></a>
            <a :href="route('users.edit', row.user_id)" title="{{ __('Edit') }}" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i></a>
            <button type="button" title="{{ __('Delete') }}" class="btn btn-sm btn-danger" x-show="row.user_id != @js(Auth::id())" :disabled="isPending('delete' + row.user_id)"
                    x-on:click="confirm(@js(__('Are you sure you want to delete ')) + row.username + '?') && request('delete' + row.user_id, 'DELETE', route('users.destroy', row.user_id))
                        .then((message) => { toastr.success(message); removeRow(row); }).catch(() => {})">
                <i class="fa fa-trash"></i>
            </button>
        </x-data-table.column>
    </x-data-table>
</div>
@endsection
