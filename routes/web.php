<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(route('cesium.index'));
});

Route::group(['prefix' => 'cesium'], function () {
    Route::get('/', function () {
        return view('cesium.demo');
    })->name('cesium.index');
    Route::post('waypoints', [])->name('cesium.waypoints_add');
});
