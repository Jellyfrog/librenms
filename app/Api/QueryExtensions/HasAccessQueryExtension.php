<?php

/**
 * HasAccessQueryExtension.php
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
 * same one the web UI filters with. A model without that scope is left alone:
 * its policy is then the only thing guarding it.
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
        if (! $builder->getModel()->hasNamedScope('hasAccess')) {
            return $builder;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return $builder->whereRaw('1 = 0'); // no user, no rows
        }

        return $builder->scopes(['hasAccess' => [$user]]);
    }
}
