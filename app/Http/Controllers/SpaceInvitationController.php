<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvitationRequest;
use App\Mail\SpaceInvitationMail;
use App\Models\Invitation;
use App\Models\Space;
use App\Models\SpaceUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SpaceInvitationController extends Controller
{
    public function index(Space $space)
    {
        $invites = $space->invitations;
        return response()->json($invites);
    }

    /**
     * @throws \Throwable
     */
    public function store(StoreInvitationRequest $request, Space $space)
    {
        $email = $request->email;
        $isMember = $space->users()->where('email', $email)->exists();
        if ($isMember) {
            return response()->json(['message' => '이미 이 공간의 멤버입니다.'], 409);
        }

        $existingInvite = Invitation::where('space_id', $space->id)
            ->where('email', $email)
            ->where('status', 'PENDING')
            ->first();

        if ($existingInvite) {
            return response()->json(['message' => '이미 대기 중인 초대가 있습니다.'], 409);
        }

        $invitation = DB::transaction(function () use ($request, $space, $email) {
            $token = Str::random(64);

            $invitation = Invitation::create([
                'email' => $email,
                'token' => $token,
                'user_space_role' => $request->role,
                'space_id' => $space->id,
                'inviter_id' => $request->user()->id,
                'expires_at' => now()->addDays(3), // 3일 유효
                'status' => 'PENDING',
            ]);

            Mail::to($email)->send(new SpaceInvitationMail($invitation));

        });
    }

    public function accept(Request $request)
    {
        $request->validate(['token' => 'required']);
        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'PENDING')
            ->first();

        if (!$invitation) {
            return response()->json(['message' => '유효하지 않거나 만료된 초대장입니다.'], 404);
        }


        if ($invitation->email !== auth()->user()->email) {
            return response()->json([
                'message' => '초대받은 이메일 계정으로 로그인해야 합니다.',
            ], 403); // 403 Forbidden 권장
        }

        $exists = SpaceUser::where('space_id', $invitation->space_id)
            ->where('user_id', auth()->id())
            ->exists();


        if ($exists) {
            return response()->json(['message' => '이미 멤버입니다.'], 409);
        }

        DB::transaction(function () use ($invitation) {
            SpaceUser::create([
                'user_id' => auth()->id(),
                'space_id' => $invitation->space_id,
                'role' => $invitation->user_space_role,
            ]);

            $invitation->update(['status' => 'ACCEPTED']);
        });
    }
}
