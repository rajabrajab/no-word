<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Resources\HelpingMethodResource;
use App\Models\HelpingMethod;
use Illuminate\Http\Request;

class HelpingMethodController extends Controller
{
    public function index(Request $request)
    {
        $helpingMethods = HelpingMethod::ordered()->get();

        return response()->sendResponse(
            HelpingMethodResource::collection($helpingMethods),
            ResponseMessages::INDEX_SUCCESS
        );
    }
}
