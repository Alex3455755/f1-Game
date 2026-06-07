<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;

class TeamController extends Controller
{
    public function index()
    {
        return response()->json(
            Team::with('drivers')->orderBy('overall_rating', 'desc')->get()
        );
    }

    public function show(string $id)
    {
        return response()->json(
            Team::with('drivers')->findOrFail($id)
        );
    }
}
