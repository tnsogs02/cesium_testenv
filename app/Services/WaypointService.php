<?php

namespace App\Services;

use App\Models\Waypoints;

class WaypointService {
    public function addWaypoints($waypoints) {
<<<<<<< HEAD
        Waypoints::truncate();
=======
        $this->clearWaypoints();
>>>>>>> a8c2fc1 (complete - waypoints read/write)
        Waypoints::insert($waypoints);
    }

    public function getWaypoints() {
        return Waypoints::all();
<<<<<<< HEAD
=======
    }

    public function clearWaypoints() {
        Waypoints::truncate();
>>>>>>> a8c2fc1 (complete - waypoints read/write)
    }
}
