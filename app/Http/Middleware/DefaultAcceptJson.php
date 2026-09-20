<?php

/**
 * DefaultAcceptJson.php
 *
 * Accept header default for the v2 API
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pick JSON for clients that did not ask for anything in particular.
 *
 * Unlike EnforceJson this leaves an explicit Accept header alone, so content
 * negotiation (JSON-LD, JSON:API) keeps working. It is
 * what stops an unauthenticated `curl /api/v2/...` being answered with a
 * redirect to the login page instead of a 401.
 *
 * Exceptions::shouldRenderJsonWhen() does not work here: API Platform decorates
 * the exception handler, and its ErrorHandler hands anything it cannot render
 * itself to the original Laravel handler, which never sees the callback.
 */
class DefaultAcceptJson
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->acceptsAnyContentType()) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
