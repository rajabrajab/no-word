<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Resources\LandingShowcaseResource;
use App\Services\LandingShowcaseService;
use Illuminate\Http\Response;

class LandingController extends Controller
{
    public function __construct(private LandingShowcaseService $showcase) {}

    /**
     * Sample categories and questions for the public landing page.
     *
     * Open on purpose: the page is unauthenticated marketing. It hands out a few real
     * question and answer pairs each time, so keep the sample small — this is the one
     * route that answers with an answer nobody had to scan a card for.
     */
    public function index(): Response
    {
        return response()->sendResponse(
            new LandingShowcaseResource($this->showcase->showcase()),
            ResponseMessages::INDEX_SUCCESS
        );
    }
}
