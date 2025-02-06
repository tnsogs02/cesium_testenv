<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waypoints;

class WaypointController extends Controller
{

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function addWaypoints() {
        dd($this->request->get('waypoints'));
    }
}
