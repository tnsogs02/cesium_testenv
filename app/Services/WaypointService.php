<?php

namespace App\Services;

use App\Models\Waypoints;

class WaypointService {
    public function addWaypoints($waypoints) {
        Waypoints::truncate();
        Waypoints::insert($waypoints);
    }

    public function getWaypoints() {
        return Waypoints::all();
    }
}
