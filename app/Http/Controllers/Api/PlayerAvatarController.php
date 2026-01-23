<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerAvatarResource;
use App\Models\PlayerAvatar;
use Illuminate\Http\Request;

class PlayerAvatarController extends Controller
{
    public function index(Request $request)
    {
        $playerAvatars = PlayerAvatar::all();

        return response()->sendResponse(
            PlayerAvatarResource::collection($playerAvatars),
            ResponseMessages::INDEX_SUCCESS
        );
    }
}

