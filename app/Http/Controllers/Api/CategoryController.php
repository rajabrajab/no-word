<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()
            ->with('country')
            ->byCountry($request->country_id)
            ->byLanguage($request->language)
            ->search();

        $categories = $query->get();

        return response()->sendResponse(
            CategoryResource::collection($categories),
            ResponseMessages::INDEX_SUCCESS
        );
    }
}
