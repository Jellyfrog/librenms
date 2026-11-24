<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\SslCertificateResource;
use App\Models\SslCertificate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SslCertificateController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SslCertificate::class);

        $query = SslCertificate::hasAccess($request->user())->with('device:device_id,hostname');
        $request->searchQuery($query, ['host', 'subject', 'issuer']);
        $request->sortQuery($query, ['id', 'host', 'port', 'subject', 'issuer', 'valid_to', 'days_until_expiry', 'last_checked_at', 'disabled'], ['host' => 'asc']);

        return SslCertificateResource::collection($request->paginate($query));
    }
}
