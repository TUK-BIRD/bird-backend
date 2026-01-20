<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\SpaceInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/space/{space}/rooms', [RoomController::class, 'index']);

    Route::get('/spaces/{space}/invites', [SpaceInvitationController::class, 'index']);
    Route::post('/spaces/{space}/invite', [SpaceInvitationController::class, 'store']);
    Route::post('/spaces/invite/accept', [SpaceInvitationController::class, 'accept']);

});
