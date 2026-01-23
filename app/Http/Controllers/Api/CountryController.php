<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $query = Country::query()
            ->search();

        $countries = $query->get();

        return response()->sendResponse(
            CountryResource::collection($countries),
            ResponseMessages::INDEX_SUCCESS
        );
    }
}

