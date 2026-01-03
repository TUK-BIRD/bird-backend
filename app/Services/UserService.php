<?php

namespace App\Services;

use App\Enums\UserSpaceRole;
use App\Models\Space;
use App\Models\User;
use App\Models\UserRole;
use App\Models\SpaceUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
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
            'space_name' => 'required|string|max:255',
            'space_description' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $space = Space::create([
                'name' => $data['space_name'],
                'description' => $data['space_description'],
            ]);

            $userRole = UserRole::create([
                'user_id' => $user->id,
                'role' => \App\Enums\UserRole::ADMIN
            ]);

            $userSpace = SpaceUser::create([
                'user_id' => $user->id,
                'space_id' => $space->id,
                'role' => UserSpaceRole::OWNER
            ]);

            return "asdf";
        });
    }

    public function login()
    {

    }

    public function logout()
    {

    }


}
