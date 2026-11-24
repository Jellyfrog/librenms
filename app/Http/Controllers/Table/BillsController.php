<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\BillResource;
use App\Models\Bill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BillsController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Bill::class);
        $request->validate([
            'period' => 'nullable|in:prev',
            'bill_type' => 'nullable|in:cdr,quota',
            'state' => 'nullable|in:under,over',
        ]);

        $previous = $request->input('period') === 'prev';

        $query = Bill::hasAccess($request->user());

        if ($previous) {
            $latestHistory = DB::table('bill_history')
                ->select('bill_id')
                ->selectRaw('MAX(bill_hist_id) AS bill_hist_id')
                ->where('bill_dateto', '<', now())
                ->where('bill_dateto', '>', now()->subDays(40))
                ->groupBy('bill_id');

            $query->joinSub($latestHistory, 'latest_history', 'bills.bill_id', '=', 'latest_history.bill_id')
                ->join('bill_history', 'bill_history.bill_hist_id', '=', 'latest_history.bill_hist_id')
                ->select([
                    'bills.bill_id', 'bills.bill_name', 'bills.bill_notes', 'bills.bill_day',
                    'bill_history.bill_type', 'bill_history.bill_allowed', 'bill_history.bill_percent', 'bill_history.bill_overuse',
                    'bill_history.rate_95th', 'bill_history.rate_95th_in', 'bill_history.rate_95th_out',
                    'bill_history.bill_datefrom', 'bill_history.bill_dateto',
                    'bill_history.traf_total as total_data', 'bill_history.traf_in as total_data_in', 'bill_history.traf_out as total_data_out',
                ]);
            $table = 'bill_history';
            $allowed = 'bill_history.bill_allowed';
            $used = "CASE WHEN LOWER(bill_history.bill_type) = 'cdr' THEN bill_history.rate_95th ELSE bill_history.traf_total END";
        } else {
            $allowed = "CASE WHEN LOWER(bills.bill_type) = 'cdr' THEN bills.bill_cdr ELSE bills.bill_quota END";
            $used = "CASE WHEN LOWER(bills.bill_type) = 'cdr' THEN bills.rate_95th ELSE bills.total_data END";
            $query->select('bills.*')->selectRaw("$allowed AS bill_allowed");
            $table = 'bills';
        }

        if ($request->filled('bill_type')) {
            $query->whereRaw("LOWER($table.bill_type) = ?", [$request->input('bill_type')]);
        }

        match ($request->input('state')) {
            'under' => $query->whereRaw("$used <= $allowed"),
            'over' => $query->whereRaw("$used > $allowed"),
            default => null,
        };

        $request->searchQuery($query, ['bill_name']);
        $request->sortQuery($query, [
            'bill_name' => 'bills.bill_name',
            'bill_type' => "$table.bill_type",
            'bill_allowed' => 'bill_allowed',
            'total_data_in' => 'total_data_in',
            'total_data_out' => 'total_data_out',
            'total_data' => 'total_data',
            'rate_95th' => "$table.rate_95th",
        ], ['bill_name' => 'asc']);

        return BillResource::collection($request->paginate($query));
    }
}
