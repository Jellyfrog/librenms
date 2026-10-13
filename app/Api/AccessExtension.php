<?php

namespace App\Api;

use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Metadata\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Limits every API query, lists and single items alike, to what the current
 * user may access, using the model's hasAccess scope. Anything outside that is
 * simply not found.
 *
 * This covers the query for the requested resource only, not relations read
 * from its models, which is why the resources expose none: a related resource
 * is referred to by its id and has a route of its own.
 */
class AccessExtension implements QueryExtensionInterface
{
    public function apply(Builder $builder, array $uriVariables, Operation $operation, $context = []): Builder
    {
        $model = $builder->getModel();
        if (! method_exists($model, 'scopeHasAccess')) {
            throw new \LogicException($model::class . ' has no hasAccess scope, so the API cannot limit it to what a user may see.');
        }

        $user = Auth::user();

        // never widen access when there is nobody to check it against
        return $user === null ? $builder->whereRaw('1 = 0') : $model->scopeHasAccess($builder, $user);
    }
}
