<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\UseHelpingMethodRequest;
use App\Http\Resources\GameBoardResource;
use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Services\GameService;

class GameController extends Controller
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function store(CreateGameRequest $request)
    {
        $data = $request->validated();
        $game = $this->gameService->createGame($data);

        return response()->sendResponse(
            ['game_id' => $game->id],
            ResponseMessages::CREATE_SUCCESS
        );
    }
    public function gameBoard(Game $game)
    {
        $game = $this->gameService->getGameBoard($game);

        return response()->sendResponse(
            new GameBoardResource($game),
            'Game board retrieved successfully.'
        );
    }

    public function useHelpingMethod(UseHelpingMethodRequest $request, $teamId)
    {
        $helpingMethodId = $request->validated()['helping_method_id'];
        $game = $this->gameService->useHelpingMethod($teamId, $helpingMethodId);

        return response()->sendResponse(
            new GameResource($game),
            'Helping method marked as used successfully.'
        );
    }
}

