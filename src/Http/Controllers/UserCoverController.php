<?php

declare(strict_types=1);

namespace Subarist\WaterholeUserCover\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rules\File;
use Subarist\WaterholeUserCover\Services\UserCoverService;
use Waterhole\Http\Controllers\Controller;
use Waterhole\Models\User;

class UserCoverController extends Controller
{
    public function editor(Request $request, User $user): View
    {
        $this->authorizeCoverAccess($request, $user);

        return view('waterhole-user-cover::actions.edit-cover-modal', [
            'user' => $user,
        ]);
    }

    public function upload(
        Request $request,
        User $user,
        UserCoverService $service,
    ): JsonResponse {
        $this->authorizeCoverAccess($request, $user);

        $request->validate([
            'cover' => [
                'required',
                File::types([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])->extensions([
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                ])->max(UserCoverService::MAX_UPLOAD_KB),
            ],
            'position_x' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'position_y' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        $cover = $service->upload(
            $user,
            $request->file('cover'),
            (int) $request->input('position_x', 50),
            (int) $request->input('position_y', 50),
        );

        return response()->json([
            'url' => $cover->url,
            'position_x' => $cover->position_x,
            'position_y' => $cover->position_y,
        ]);
    }

    public function position(
        Request $request,
        User $user,
        UserCoverService $service,
    ): JsonResponse {
        $this->authorizeCoverAccess($request, $user);

        $request->validate([
            'position_y' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        $cover = $service->updatePosition(
            $user,
            (int) $request->input('position_y'),
        );

        if (!$cover) {
            return response()->json([
                'message' => '目前沒有封面。',
            ], 404);
        }

        return response()->json([
            'url' => $cover->url,
            'position_x' => $cover->position_x,
            'position_y' => $cover->position_y,
        ]);
    }

    public function remove(
        Request $request,
        User $user,
        UserCoverService $service,
    ): JsonResponse {
        $this->authorizeCoverAccess($request, $user);

        $service->remove($user);

        return response()->json([
            'removed' => true,
        ]);
    }

    private function authorizeCoverAccess(Request $request, User $user): void
    {
        $actor = $request->user();

        abort_unless(
            $actor !== null
                && (
                    $actor->isAdmin()
                    || (
                        $actor->is($user)
                        && $actor->can('waterhole.user-cover', User::class)
                    )
                ),
            403,
        );
    }
}
