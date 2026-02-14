<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\CreateRandomGameRequest;
use App\Http\Requests\ReplaceQuestionRequest;
use App\Http\Requests\ResetGameRequest;
use App\Http\Requests\UseHelpingMethodRequest;
use App\Http\Resources\GameBoardResource;
use App\Http\Resources\GameResource;
use App\Http\Resources\MyGameResource;
use App\Http\Resources\QuestionResource;
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

    public function createRandom(CreateRandomGameRequest $request)
    {
        $data = $request->validated();
        $game = $this->gameService->createRandomGame($data, auth()->user()->id);

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


        if($helpingMethodId == 2) {
            $result = $this->gameService->replaceQuestion($team->game, $request->validated()['question_id']);
        }

        $this->gameService->useHelpingMethod($teamId, $helpingMethodId);

        $result = [];

        return response()->sendResponse($result,
            'Helping method marked as used successfully.'
        );
    }

    public function addQuestionScore(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'team_id' => 'nullable|exists:teams,id',
            'game_id' => 'required|exists:games,id',
        ]);

        $game = Game::findOrFail($request->game_id);

        if ($game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this game.');
        }

        $question = Question::findOrFail($request->question_id);
        $teamId = $request->team_id;

        DB::transaction(function () use ($teamId, $question, $game) {
            if ($teamId) {
                $team = Team::findOrFail($teamId);

                if ($team->game_id !== $game->id) {
                    throw new \Exception('Team does not belong to this game.');
                }

                $team->increment('score', $question->score ?? 0);
            }

            DB::table('game_questions')
                ->where('game_id', $game->id)
                ->where('question_id', $question->id)
                ->update(['is_answered' => 1]);
        });


        return response()->sendResponse(
            [],
            'Question marked as answered successfully.'
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

    public function replaceQuestion(ReplaceQuestionRequest $request, Game $game)
    {
        if ($game->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this game.');
        }

        $questionId = $request->validated()['question_id'];

        try {
            $result = $this->gameService->replaceQuestion($game, $questionId);

            return response()->sendResponse(new QuestionResource($result),
                'Question replaced successfully.'
            );
        } catch (\Exception $e) {
            return response()->sendError(400, $e->getMessage());
        }
    }
}

