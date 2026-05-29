<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('admin/Locations', [
            'locations' => Location::withCount('albums')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $location = new Location;
        $location->name = $request->name;
        $location->lat = $request->lat;
        $location->lng = $request->lng;
        $location->save();

        return $location;
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Location $location)
    {
        $location->name = $request->name;
        $location->lat = $request->lat;
        $location->lng = $request->lng;
        $location->save();

        return $location;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $location->delete();
    }
}
