<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): JsonResponse
    {
        $overview = $dashboard->overview(
            recentLimit: config('savings.dashboard.recent_activity_limit'),
            chartMonths: config('savings.dashboard.chart_months'),
        );

        $overview['recent_activities'] = TransactionResource::collection($overview['recent_activities']);

        return response()->json(['data' => $overview]);
    }
}
