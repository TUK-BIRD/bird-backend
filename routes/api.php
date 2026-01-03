<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
   Route::get('/user', function (Request $request) {
       return $request->user();
   });
   Route::get('/logout', [AuthController::class, 'logout']);
});
