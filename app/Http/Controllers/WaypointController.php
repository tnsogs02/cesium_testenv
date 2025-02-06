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
<<<<<<< HEAD
        $waypoints = $request->get('waypoints');
        if(!is_array($waypoints) || count($waypoints) < 1) {
            return response()->json(['status' => 'failed', 'description' => 'No waypoints provided']);
        }
        $this->waypointService->addWaypoints($request->get('waypoints'));
        return response()->json(['status' => 'success']);
=======
        $waypoints = $request->get('waypoints', null);
        if($waypoints == null) {
            $this->waypointService->clearWaypoints();
        } else {
            $this->waypointService->addWaypoints($request->get('waypoints'));
        }
        return response()->json(['status' => 'success', 'waypoints' => $waypoints]);
>>>>>>> a8c2fc1 (complete - waypoints read/write)
    }

    public function getWaypoints() {
        $waypoints = $this->waypointService->getWaypoints();
        return response()->json(['waypoints' => $waypoints]);
    }
}
