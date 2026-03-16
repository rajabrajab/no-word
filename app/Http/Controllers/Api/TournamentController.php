<?php

namespace App\Http\Controllers\Api;

use App\Constants\ResponseMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Http\Resources\TournamentTeamResource;
use App\Http\Resources\MyTournamentResource;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    protected $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    public function store(CreateTournamentRequest $request)
    {
        try {
            $data = $request->validated();
            $tournament = $this->tournamentService->createTournament($data, auth()->user()->id);

            return response()->sendResponse(
                new TournamentResource($tournament),
                ResponseMessages::CREATE_SUCCESS
            );
        } catch (\Exception $e) {
            return response()->sendError(500, $e->getMessage());
        }
    }

    public function myTournaments()
    {
        $user = auth()->user();

        $tournaments = Tournament::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->sendResponse(
            MyTournamentResource::collection($tournaments),
            ResponseMessages::INDEX_SUCCESS
        );
    }

    public function show(Tournament $tournament)
    {
        if ($tournament->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this tournament.');
        }

        try {
            $tournament = $this->tournamentService->getTournamentDetails($tournament);

            return response()->sendResponse(
                new TournamentResource($tournament),
                ResponseMessages::SHOW_SUCCESS
            );
        } catch (\Exception $e) {
            return response()->sendError(500, $e->getMessage());
        }
    }

    public function setWinner(Request $request, Tournament $tournament, TournamentMatch $match)
    {
        if ($tournament->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this tournament.');
        }

        if ($match->tournament_id !== $tournament->id) {
            return response()->sendError(400, 'Match does not belong to this tournament.');
        }

        $request->validate([
            'winner_id' => 'required|integer|exists:tournament_teams,id',
        ]);

        try {
            $tournament = $this->tournamentService->setMatchWinner($match, $request->winner_id);

            return response()->sendResponse(
                new TournamentResource($tournament),
                'Winner set successfully.'
            );
        } catch (\Exception $e) {
            return response()->sendError(500, $e->getMessage());
        }
    }

    public function linkGameId(Request $request, Tournament $tournament, TournamentMatch $match)
    {
        if ($tournament->user_id !== auth()->user()->id) {
            return response()->sendError(403, 'Unauthorized access to this tournament.');
        }

        if ($match->tournament_id !== $tournament->id) {
            return response()->sendError(400, 'Match does not belong to this tournament.');
        }

        $request->validate([
            'game_id' => 'required|integer|exists:games,id',
        ]);

        try {
            $match = $this->tournamentService->linkGameToMatch($match, $request->game_id);

            return response()->sendResponse(
                [
                    'id' => $match->id,
                    'game_id' => $match->game_id,
                    'position' => $match->position,
                    'status' => $match->status,
                    'team1' => $match->team1 ? new TournamentTeamResource($match->team1) : null,
                    'team2' => $match->team2 ? new TournamentTeamResource($match->team2) : null,
                    'winner' => $match->winner ? new TournamentTeamResource($match->winner) : null,
                ],
                'Game linked successfully.'
            );
        } catch (\Exception $e) {
            return response()->sendError(500, $e->getMessage());
        }
    }
}
