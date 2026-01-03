<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {


    }

    public function register(Request $request)
    {
        try {
            $result = $this->userService->register($request->all());
            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        \Log::info($request->user());
        return response()->json(['success' => 'true', 'message' => 'Logged out']);
    }
}
