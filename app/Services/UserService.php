<?php

namespace App\Services;

use App\Enums\UserSpaceRole;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function register($data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'space_name' => 'string|max:255',
            'space_description' => 'string|max:255',
            'skip_space_create' => 'boolean',
        ]);

        $validator->sometimes('space_name', 'required', function ($input) {
            return empty($input->skip_space_create);
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return DB::transaction(function () use ($data) {
            $skipSpaceCreate = ! empty($data['skip_space_create']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            if (! $skipSpaceCreate) {
                $space = Space::create([
                    'name' => $data['space_name'],
                    'description' => $data['space_description'],
                ]);
            }

            $userRole = UserRole::create([
                'user_id' => $user->id,
                'role' => \App\Enums\UserRole::ADMIN,
            ]);

            if (! $skipSpaceCreate) {
                $userSpace = SpaceUser::create([
                    'user_id' => $user->id,
                    'space_id' => $space->id,
                    'role' => UserSpaceRole::OWNER,
                ]);
            }

            return 'asdf';
        });
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return [
                'ok' => false,
            ];
        }

        $request->session()->regenerate();  // 세션 재생성

        return [
            'ok' => true,
            'user' => $request->user(),
        ];
    }

    public function logout() {}
}
