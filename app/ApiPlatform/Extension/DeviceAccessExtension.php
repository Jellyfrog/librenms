<?php

namespace App\ApiPlatform\Extension;

use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Metadata\Operation;
use App\Facades\Permissions;
use App\Models\DeviceRelatedModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Filters query results based on the authenticated user's device permissions.
 *
 * Admin and global-read users see all resources. Users with per-device
 * permissions only see resources belonging to their authorized devices.
 */
class DeviceAccessExtension implements QueryExtensionInterface
{
    /**
     * @param  Builder<Model>  $builder
     * @param  array<string, string>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return Builder<Model>
     */
    public function apply(Builder $builder, array $uriVariables, Operation $operation, $context = []): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $builder->whereRaw('1 = 0');
        }

        if ($user->isAdmin() || $user->hasGlobalRead()) {
            return $builder;
        }

        $model = $builder->getModel();

        // For Device model, filter directly
        if ($model instanceof \App\Models\Device) {
            $deviceIds = Permissions::devicesForUser($user);

            return $builder->whereIn('device_id', $deviceIds->toArray());
        }

        // For models related to devices, filter by device_id
        if ($model instanceof DeviceRelatedModel || $model->getTable() !== 'users') {
            if (\Schema::hasColumn($model->getTable(), 'device_id')) {
                $deviceIds = Permissions::devicesForUser($user);

                return $builder->whereIn($model->getTable() . '.device_id', $deviceIds->toArray());
            }
        }

        return $builder;
    }
}
