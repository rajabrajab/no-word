<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentRound;
use App\Models\TournamentMatch;
use App\Models\Game;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentService
{
    protected $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    /**
     * Validate match is ready for game creation
     */
    protected function validateMatchForGame(TournamentMatch $match): void
    {
        if (!$match->team1_id || !$match->team2_id) {
            throw new \Exception('Match is not ready. Both teams must be set.');
        }

        if ($match->game_id !== null) {
            throw new \Exception('Match already has a game linked.');
        }
    }

    private function getRoundName(int $roundNumber, int $totalRounds): string
    {
        if ($roundNumber == $totalRounds) {
            return "النهائي";
        } elseif ($roundNumber == $totalRounds - 1) {
            return "نصف النهائي";
        } elseif ($roundNumber == $totalRounds - 2) {
            return "ربع النهائي";
        } elseif ($roundNumber == $totalRounds - 3) {
            return "دور الـ 16";
        }
        return "دور " . $roundNumber;
    }

    public function createTournament(array $data, int $userId): Tournament
    {
        $size = (int) $data['size'];
        $teams = $data['teams'];

        if (count($teams) !== $size) {
            throw new \Exception("Team count must equal tournament size ({$size}).");
        }

        if (!in_array($size, [4, 8, 16])) {
            throw new \Exception("Tournament size must be 4, 8, or 16.");
        }

        DB::beginTransaction();

        try {
            // Create tournament
            $tournament = Tournament::create([
                'name' => $data['name'],
                'size' => $size,
                'user_id' => $userId,
                'is_completed' => false,
                'current_round' => 1,
                'completion_percentage' => 0,
            ]);

            // Create tournament teams
            $tournamentTeams = [];
            foreach ($teams as $teamData) {
                $tournamentTeams[] = Team::create([
                    'tournament_id' => $tournament->id,
                    'game_id' => null, // Will be set when game is created for match
                    'name' => $teamData['name'],
                    'avatar_id' => $teamData['avatar_id'] ?? null,
                    'score' => $teamData['score'] ?? 0,
                    'players_number' => $teamData['players_number'] ?? 0,
                ]);
            }

            // Calculate total rounds
            $totalRounds = (int) log($size, 2);
            $totalMatches = $size - 1;

            // Create rounds
            $rounds = [];
            for ($roundNum = 1; $roundNum <= $totalRounds; $roundNum++) {
                $roundName = $this->getRoundName($roundNum, $totalRounds);
                $rounds[$roundNum] = TournamentRound::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $roundNum,
                    'name' => $roundName,
                ]);
            }

            // Create Round 1 matches (seed teams)
            $matchesPerRound1 = $size / 2;
            for ($position = 0; $position < $matchesPerRound1; $position++) {
                $team1Index = $position * 2;
                $team2Index = $team1Index + 1;

                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_id' => $rounds[1]->id,
                    'position' => $position,
                    'status' => 'pending',
                    'team1_id' => $tournamentTeams[$team1Index]->id,
                    'team2_id' => $tournamentTeams[$team2Index]->id,
                    'game_id' => null,
                ]);
            }

            // Create empty matches for rounds 2 to N
            for ($roundNum = 2; $roundNum <= $totalRounds; $roundNum++) {
                $matchesInRound = $size / pow(2, $roundNum);
                for ($position = 0; $position < $matchesInRound; $position++) {
                    TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'round_id' => $rounds[$roundNum]->id,
                        'position' => $position,
                        'status' => 'pending',
                        'team1_id' => null,
                        'team2_id' => null,
                        'game_id' => null,
                    ]);
                }
            }

            DB::commit();

            return $tournament->load([
                'teams.avatar',
                'rounds.matches.team1.avatar',
                'rounds.matches.team2.avatar',
                'rounds.matches.winner.avatar',
                'rounds.matches.game',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create tournament: ' . $e->getMessage());
        }
    }


    public function getTournamentDetails(Tournament $tournament): Tournament
    {
        // Calculate completion percentage
        $totalMatches = $tournament->size - 1;
        $completedMatches = $tournament->matches()->where('status', 'completed')->count();
        $completionPercentage = $totalMatches > 0 ? ($completedMatches / $totalMatches) : 0;

        $tournament->completion_percentage = round($completionPercentage, 4);

        // Update current round (find highest round with at least one completed match)
        $highestRoundWithCompleted = $tournament->matches()
            ->where('status', 'completed')
            ->join('tournament_rounds', 'tournament_matches.round_id', '=', 'tournament_rounds.id')
            ->max('tournament_rounds.round_number');

        if ($highestRoundWithCompleted) {
            $tournament->current_round = min($highestRoundWithCompleted + 1, (int) log($tournament->size, 2));
        }

        $tournament->save();

        return $tournament->load([
            'teams.avatar',
            'rounds.matches.team1.avatar',
            'rounds.matches.team2.avatar',
            'rounds.matches.winner.avatar',
            'rounds.matches.game',
            'champion.avatar',
        ]);
    }

    public function setMatchWinner(TournamentMatch $match, int $winnerTeamId): Tournament
    {
        DB::beginTransaction();

        try {
            if ($match->status !== 'pending') {
                throw new \Exception('Match is not pending.');
            }

            if ($match->team1_id != $winnerTeamId && $match->team2_id != $winnerTeamId) {
                throw new \Exception('Winner must be one of the match teams.');
            }

            $winner = Team::findOrFail($winnerTeamId);

            // Update match
            $match->winner_id = $winnerTeamId;
            $match->status = 'completed';
            $match->save();

            $tournament = $match->tournament;
            $round = $match->round;
            $totalRounds = (int) log($tournament->size, 2);

            // If not final round, advance winner to next round
            if ($round->round_number < $totalRounds) {
                $nextRoundNumber = $round->round_number + 1;
                $nextMatchPosition = intval($match->position / 2);
                $isFirstTeam = ($match->position % 2 == 0);

                $nextRound = TournamentRound::where('tournament_id', $tournament->id)
                    ->where('round_number', $nextRoundNumber)
                    ->first();

                $nextMatch = TournamentMatch::where('tournament_id', $tournament->id)
                    ->where('round_id', $nextRound->id)
                    ->where('position', $nextMatchPosition)
                    ->first();

                if ($isFirstTeam) {
                    $nextMatch->team1_id = $winnerTeamId;
                } else {
                    $nextMatch->team2_id = $winnerTeamId;
                }
                $nextMatch->save();
            } else {
                $tournament->champion_id = $winnerTeamId;
                $tournament->is_completed = true;
            }

            $totalMatches = $tournament->size - 1;
            $completedMatches = $tournament->matches()->where('status', 'completed')->count();
            $tournament->completion_percentage = round($completedMatches / $totalMatches, 4);

            $highestRoundWithCompleted = $tournament->matches()
                ->where('status', 'completed')
                ->join('tournament_rounds', 'tournament_matches.round_id', '=', 'tournament_rounds.id')
                ->max('tournament_rounds.round_number');

            if ($highestRoundWithCompleted) {
                $tournament->current_round = min($highestRoundWithCompleted + 1, $totalRounds);
            }

            $tournament->save();

            DB::commit();

            return $this->getTournamentDetails($tournament);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to set match winner: ' . $e->getMessage());
        }
    }

    public function linkGameToMatch(TournamentMatch $match, int $gameId): TournamentMatch
    {
        if ($match->status !== 'pending') {
            throw new \Exception('Match is not pending.');
        }

        $this->validateMatchForGame($match);

        $game = Game::find($gameId);
        if (!$game) {
            throw new \Exception('Game not found.');
        }

        $match->game_id = $gameId;
        $match->save();

        return $match->load(['team1.avatar', 'team2.avatar', 'winner.avatar', 'game']);
    }

    public function createGameForTournament(Tournament $tournament, array $data): Game
    {
        $user = \App\Models\User::findOrFail($tournament->user_id);
        $teamIds = $data['teams'];

        if (count($teamIds) !== 2) {
            throw new \Exception('Exactly 2 teams are required.');
        }

        $team1 = Team::findOrFail($teamIds[0]);
        $team2 = Team::findOrFail($teamIds[1]);

        if ($team1->tournament_id !== $tournament->id || $team2->tournament_id !== $tournament->id) {
            throw new \Exception('Teams must belong to the specified tournament.');
        }

        $isDefault = $this->gameService->checkUserGamesRemaining($user);

        DB::beginTransaction();

        try {
            $game = Game::create([
                'user_id' => $user->id,
                'status' => 'active',
                'tournament_game' => true,
            ]);

            if (!$isDefault) {
                $user->decrementGamesRemaining();
            } else {
                $user->update(['has_used_default_game' => true]);
            }

            $team1->update(['game_id' => $game->id]);
            $team2->update(['game_id' => $game->id]);

            if (isset($data['categories']) && !empty($data['categories'])) {
                $this->gameService->attachCategoriesAndQuestions($game, $data['categories']);
            }

            $match = TournamentMatch::where('tournament_id', $tournament->id)
                ->where(function ($query) use ($team1, $team2) {
                    $query->where(function ($q) use ($team1, $team2) {
                        $q->where('team1_id', $team1->id)
                          ->where('team2_id', $team2->id);
                    })->orWhere(function ($q) use ($team1, $team2) {
                        $q->where('team1_id', $team2->id)
                          ->where('team2_id', $team1->id);
                    });
                })
                ->whereNull('game_id')
                ->first();

            if ($match) {
                $match->game_id = $game->id;
                $match->save();
            }

            DB::commit();

            return $game->load(['teams.avatar', 'categories', 'questions.category']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create game for tournament: ' . $e->getMessage());
        }
    }
}
