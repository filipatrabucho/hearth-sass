<?php

namespace App\Http\Controllers\Api;

use App\Domain\User\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Every HearthGG user (super admin only - this spans every client).
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_super_admin, 403);

        return response()->json(User::orderBy('username')->get());
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless(
            $request->user()->is_super_admin || $request->user()->is($user),
            403
        );

        return response()->json($user);
    }
}
