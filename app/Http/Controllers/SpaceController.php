<?php

namespace App\Http\Controllers;

use App\Enums\UserSpaceRole;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\Space;
use App\Models\SpaceUser;
use App\Models\User;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    /**
     * 전체 Space 조회
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection|mixed
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

    /**
     * 해당 공간의 유저 조회
     * @param Space $space
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function members(Space $space)
    {
        $space_users = SpaceUser::with('user')
            ->where('space_id', $space->id)
            ->get();
        return $space_users;
    }

    /**
     * 공간 멤버 권한 변경
     * @param UpdateMemberRoleRequest $request
     * @param Space $space
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMemberRole(UpdateMemberRoleRequest $request, Space $space, User $user)
    {
        $actorRole = SpaceUser::where('space_id', $space->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        if ($actorRole !== UserSpaceRole::OWNER) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $target = SpaceUser::where('space_id', $space->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$target) {
            return response()->json(['message' => '해당 멤버를 찾을 수 없습니다.'], 404);
        }

        $newRole = UserSpaceRole::from($request->role);

        if ($target->role === UserSpaceRole::OWNER && $newRole !== UserSpaceRole::OWNER) {
            $ownerCount = SpaceUser::where('space_id', $space->id)
                ->where('role', UserSpaceRole::OWNER)
                ->count();

            if ($ownerCount <= 1) {
                return response()->json(['message' => '마지막 OWNER는 권한을 변경할 수 없습니다.'], 409);
            }
        }

        if ($target->role === $newRole) {
            return response()->json($target->fresh(), 200);
        }

        $target->update(['role' => $newRole]);

        return response()->json($target->fresh(), 200);
    }

    /**
     * 공간 멤버 삭제
     * @param Request $request
     * @param Space $space
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, Space $space, User $user)
    {
        $actorRole = SpaceUser::where('space_id', $space->id)
            ->where('user_id', $request->user()->id)
            ->value('role');

        if ($actorRole !== UserSpaceRole::OWNER) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $target = SpaceUser::where('space_id', $space->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$target) {
            return response()->json(['message' => '해당 멤버를 찾을 수 없습니다.'], 404);
        }

        if ($target->role === UserSpaceRole::OWNER) {
            $ownerCount = SpaceUser::where('space_id', $space->id)
                ->where('role', UserSpaceRole::OWNER)
                ->count();

            if ($ownerCount <= 1) {
                return response()->json(['message' => '마지막 OWNER는 삭제할 수 없습니다.'], 409);
            }
        }

        $target->delete();

        return response()->json(['message' => '삭제되었습니다.'], 200);
    }
}
