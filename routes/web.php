<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaypointController;

Route::get('/', function () {
    return redirect(route('cesium.index'));
});

Route::group(['prefix' => 'cesium'], function () {
    Route::get('/', function () {
        return view('cesium.demo');
    })->name('cesium.index');
    Route::post('waypoints', [WaypointController::class, 'addWaypoints'])->name('cesium.waypoints_add');
});
