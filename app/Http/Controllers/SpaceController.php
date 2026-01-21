<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\SpaceUser;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        return $request->user()->spaces()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Space $space)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Space $space)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Space $space)
    {
        //
    }

    public function members(Request $request, Space $space)
    {
        $space_users = SpaceUser::with('user')
            ->where('space_id', $space->id)
            ->get();
        return $space_users;
    }
}
