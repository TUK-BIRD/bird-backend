<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BLEAnchorController;
use App\Http\Controllers\BleScanEventController;
use App\Http\Controllers\RadiomapController;
use App\Http\Controllers\ReferencePointController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\SpaceInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [AuthController::class, 'token']); // 모바일 앱 용 라우트
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    // 모바일 앱 용 라우트
    Route::post('/auth/token/logout', [AuthController::class, 'logoutToken']);
});

Route::middleware('auth:sanctum')->group(function () {
    // ESP32 Setup 라우트
    Route::get('/ble_anchors', [BLEAnchorController::class, 'index']);
    Route::post('/ble_anchors', [BLEAnchorController::class, 'store']);
    Route::delete('/ble_anchors/{anchor}', [BLEAnchorController::class, 'destroy']);

    // Reference Point 라우트
    Route::get('/reference_points', [ReferencePointController::class, 'index']);
    Route::post('/reference_points', [ReferencePointController::class, 'store']);

    // 라디오 앱 구축 라우트
    Route::post('/radiomap/create', [RadiomapController::class, 'store']);

    // 공간 관련 라우트
    Route::post('/spaces', [SpaceController::class, 'store']);
    Route::get('/spaces', [SpaceController::class, 'index']);
    Route::get('/space/{space}/members', [SpaceController::class, 'members']);
    Route::patch('/space/{space}/members/{user}/role', [SpaceController::class, 'updateMemberRole']);
    Route::delete('/space/{space}/members/{user}', [SpaceController::class, 'removeMember']);
    Route::get('/space/{space}/rooms', [RoomController::class, 'index']);
    Route::get('/spaces/{space}/rooms/{room}', [RoomController::class, 'show']);
    Route::get('/spaces/{space}/rooms/{room}/ble_anchors', [BLEAnchorController::class, 'roomIndex']);
    Route::get('/spaces/{space}/rooms/{room}/ble_anchors/health', [BLEAnchorController::class, 'roomHealthIndex']);
    Route::get('/spaces/{space}/rooms/{room}/scan-status', [BLEAnchorController::class, 'latestScanStatus']);
    Route::post('/spaces/{space}/rooms/{room}/scan-control', [BLEAnchorController::class, 'controlRoomScan']);
    Route::post('/space/{space}/room/create', [RoomController::class, 'store']);
    Route::patch('/spaces/{space}/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/spaces/{space}/rooms/{room}', [RoomController::class, 'destroy']);

    Route::get('/spaces/{space}/invites', [SpaceInvitationController::class, 'index']);
    Route::post('/spaces/{space}/invite', [SpaceInvitationController::class, 'store']);
    Route::post('/spaces/invite/accept', [SpaceInvitationController::class, 'accept']);
    Route::get('/spaces/{space}/rooms/{room}/ble_scan_events/dashboard', [BleScanEventController::class, 'dashboard']);
    Route::get('/spaces/{space}/rooms/{room}/ble_scan_events/multi-anchor-dashboard', [BleScanEventController::class, 'multiAnchorDashboard']);
});
