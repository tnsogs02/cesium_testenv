<?php

namespace App\Services;

use App\Models\Waypoints;

class WaypointService {
    public function addWaypoints($waypoints) {
        $this->clearWaypoints();
        Waypoints::insert($waypoints);
    }

    public function getWaypoints() {
        return Waypoints::all();
    }

    public function clearWaypoints() {
        Waypoints::truncate();
    }
}
