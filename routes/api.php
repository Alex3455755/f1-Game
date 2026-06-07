<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\QualifyingController;
use App\Http\Controllers\Api\RaceController;
use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\SetupController;
use App\Http\Controllers\Api\TeamController;

// Teams
Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{id}', [TeamController::class, 'show']);

// Seasons
Route::get('/seasons', [SeasonController::class, 'index']);
Route::post('/seasons', [SeasonController::class, 'store']);
Route::get('/seasons/current', [SeasonController::class, 'current']);
Route::get('/seasons/{id}', [SeasonController::class, 'show']);
Route::get('/seasons/{id}/standings', [SeasonController::class, 'standings']);

// Car Setup
Route::get('/weekends/{weekendId}/setup', [SetupController::class, 'show']);
Route::put('/weekends/{weekendId}/setup/{driverId}', [SetupController::class, 'update']);
Route::get('/weekends/{weekendId}/setup/{driverId}/hint', [SetupController::class, 'hint']);

// Qualifying
Route::post('/weekends/{weekendId}/qualifying/simulate', [QualifyingController::class, 'simulate']);
Route::get('/weekends/{weekendId}/qualifying/results', [QualifyingController::class, 'results']);

// Race
Route::post('/weekends/{weekendId}/race/init', [RaceController::class, 'init']);
Route::post('/weekends/{weekendId}/race/lap', [RaceController::class, 'lap']);
Route::get('/weekends/{weekendId}/race/state', [RaceController::class, 'state']);
Route::post('/weekends/{weekendId}/race/radio/{driverId}', [RaceController::class, 'radio']);
Route::post('/weekends/{weekendId}/race/pit/{driverId}', [RaceController::class, 'pit']);
Route::post('/weekends/{weekendId}/next-round', [RaceController::class, 'nextRound']);
