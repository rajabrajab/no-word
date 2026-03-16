<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentRound;
use App\Models\TournamentMatch;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TournamentService
{

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
                $tournamentTeams[] = TournamentTeam::create([
                    'tournament_id' => $tournament->id,
                    'name' => $teamData['name'],
                    'avatar_id' => $teamData['avatar_id'] ?? null,
                    'score' => $teamData['score'] ?? 0,
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

            $winner = TournamentTeam::findOrFail($winnerTeamId);

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
        if (!$match->team1_id || !$match->team2_id) {
            throw new \Exception('Match is not ready. Both teams must be set.');
        }

        if ($match->status !== 'pending') {
            throw new \Exception('Match is not pending.');
        }

        if ($match->game_id !== null) {
            throw new \Exception('Match already has a game linked.');
        }

        $game = Game::find($gameId);
        if (!$game) {
            throw new \Exception('Game not found.');
        }

        $match->game_id = $gameId;
        $match->save();

        return $match->load(['team1.avatar', 'team2.avatar', 'winner.avatar', 'game']);
    }
}
