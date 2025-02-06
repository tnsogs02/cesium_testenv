<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WaypointService;

class WaypointController extends Controller
{

    protected $waypointService;

    public function __construct(WaypointService $waypointService) {
        $this->waypointService = $waypointService;
    }

    public function addWaypoints(Request $request) {
        $waypoints = $request->get('waypoints');
        if(!is_array($waypoints) || count($waypoints) < 1) {
            return response()->json(['status' => 'failed', 'description' => 'No waypoints provided']);
        }
        $this->waypointService->addWaypoints($request->get('waypoints'));
        return response()->json(['status' => 'success']);
    }

    public function getWaypoints() {
        $waypoints = $this->waypointService->getWaypoints();
        return response()->json(['waypoints' => $waypoints]);
    }
}
