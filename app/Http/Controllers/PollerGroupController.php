<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Models\PollerGroup;
use Illuminate\Http\Request;

class PollerGroupController extends Controller
{
    public function destroy(Request $request, PollerGroup $pollergroup): JsonResponse
    {
        if ($request->user()->isAdmin()) {
            $pollergroup->delete();

            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['status' => 'failure']);
        }
    }
}
