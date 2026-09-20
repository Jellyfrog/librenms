<?php

/**
 * HasAccessQueryExtension.php
 *
 * Row level access control for the v2 API
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
 */

namespace App\Api\QueryExtensions;

use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Metadata\Operation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Limit API Platform queries to the rows the authenticated user may see.
 *
 * API Platform discovers this class automatically and applies it to every
 * Eloquent query it builds, so it reuses each model's own hasAccess scope, the
 * same one the web UI filters with.
 *
 * It denies rather than defers when there is nothing to filter with. A policy
 * only guards the item operation; a collection would hand back the whole table
 * on a model that forgot the scope, so a new resource has to opt in to being
 * readable rather than out of being filtered.
 */
class HasAccessQueryExtension implements QueryExtensionInterface
{
    /**
     * @param  Builder<Model>  $builder
     * @param  array<string, string>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return Builder<Model>
     */
    public function apply(Builder $builder, array $uriVariables, Operation $operation, $context = []): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $builder->getModel()->hasNamedScope('hasAccess')) {
            return $builder->whereRaw('1 = 0');
        }

        return $builder->scopes(['hasAccess' => [$user]]);
    }
}
