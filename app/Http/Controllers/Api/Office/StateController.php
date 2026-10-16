<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Http\Presenters\OfficeStatePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
    /** Everything the panel shows (replaces ADM.state in localStorage). */
    public function show(Request $request, OfficeStatePresenter $presenter): JsonResponse
    {
        return response()->json(['data' => $presenter->state($request->user())]);
    }
}
