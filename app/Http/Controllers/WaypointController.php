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
        $waypoints = $request->get('waypoints', null);
        if($waypoints == null) {
            $this->waypointService->clearWaypoints();
        } else {
            $this->waypointService->addWaypoints($request->get('waypoints'));
        }
        return response()->json(['status' => 'success', 'waypoints' => $waypoints]);
    }

    public function getWaypoints() {
        $waypoints = $this->waypointService->getWaypoints();
        return response()->json(['waypoints' => $waypoints]);
    }
}
