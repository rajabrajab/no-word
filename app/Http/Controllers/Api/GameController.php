<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\ResetGameRequest;
use App\Http\Requests\UseHelpingMethodRequest;
use App\Http\Resources\GameBoardResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\MyGameResource;
use App\Models\Game;
use App\Models\Question;
use App\Models\Team;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $game = $this->gameService->createGame($data, auth()->user()->id);

        return response()->sendResponse(
            ['game_id' => $game->id],
            ResponseMessages::CREATE_SUCCESS
        );
    }
    public function gameBoard(Request $request, Game $game)
    {
        if ($game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this game.');
        }

        $game = $this->gameService->getGameBoard($game);

        return response()->sendResponse(
            new GameBoardResource($game),
            'Game board retrieved successfully.'
        );
    }

    public function useHelpingMethod(UseHelpingMethodRequest $request, $teamId)
    {
        $team = Team::with('game')->findOrFail($teamId);

        if ($team->game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this team.');
        }

        $helpingMethodId = $request->validated()['helping_method_id'];
        $game = $this->gameService->useHelpingMethod($teamId, $helpingMethodId);

        return response()->sendResponse(
            [],
            'Helping method marked as used successfully.'
        );
    }

    public function addQuestionScore(Request $request, $teamId)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $team = Team::with('game')->findOrFail($teamId);

        if ($team->game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this team.');
        }

        $question = Question::findOrFail($request->question_id);

        DB::transaction(function () use ($team, $question) {
            $team->increment('score', $question->score ?? 0);
        });

        return response()->sendResponse(
            [],
            'Question score added to team successfully.'
        );
    }

    public function updateScore(Request $request, $teamId)
    {
        $request->validate([
            'score' => 'required|numeric',
        ]);

        $team = Team::with('game')->findOrFail($teamId);

        if ($team->game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this team.');
        }

        $team->update(['score' => $request->score]);

        return response()->sendResponse(
            [],
            'Team score updated successfully.'
        );
    }

    public function myGames(Request $request)
    {
        $games = Game::where('user_id', auth()->user()->id)
            ->with([
                'teams.usedHelpingMethods',
                'questions.category'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->sendResponse(
            $games->map(function ($game) {
                return new MyGameResource($game);
            }),
            'My games retrieved successfully.'
        );
    }

    public function reset(ResetGameRequest $request, Game $game)
    {
        if ($game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this game.');
        }

        $data = $request->validated();
        $game = $this->gameService->resetGame($game, $data);

        return response()->sendResponse(
            new GameBoardResource($game),
            'Game reset successfully.'
        );
    }
}

