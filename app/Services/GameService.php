<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Question;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function createGame(array $data): Game
    {
        DB::beginTransaction();

        try {
            $game = Game::create([
                'name' => $data['name'] ?? null,
                'status' => 'active',
            ]);

            $team1 = Team::create([
                'game_id' => $game->id,
                'name' => $data['team1']['name'],
                'players_number' => $data['team1']['players_number'],
                'score' => 0,
                'avatar_id' => $data['team1']['avatar_id'] ?? null,
            ]);

            $team2 = Team::create([
                'game_id' => $game->id,
                'name' => $data['team2']['name'],
                'players_number' => $data['team2']['players_number'],
                'score' => 0,
                'avatar_id' => $data['team2']['avatar_id'] ?? null,
            ]);

            $game->categories()->attach($data['categories']);


            $selectedQuestionIds = [];
            $scores = [200, 400, 600];

            foreach ($data['categories'] as $categoryId) {
                foreach ($scores as $score) {
                    $questions = Question::where('category_id', $categoryId)
                        ->where('score', $score)
                        ->inRandomOrder()
                        ->limit(2)
                        ->pluck('id')
                        ->toArray();

                    if (count($questions) > 0) {
                        $selectedQuestionIds = array_merge($selectedQuestionIds, $questions);

                        if (count($questions) == 1) {
                            $selectedQuestionIds[] = $questions[0];
                        }
                    }
                }
            }

            if (!empty($selectedQuestionIds)) {
                $game->questions()->attach($selectedQuestionIds);
            }

            DB::commit();

            return $game;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create game: ' . $e->getMessage());
        }
    }

    public function useHelpingMethod(int $teamId, int $helpingMethodId): Game
    {
        $team = Team::findOrFail($teamId);

        $alreadyUsed = $team->usedHelpingMethods()
            ->where('helping_method_id', $helpingMethodId)
            ->exists();

        if ($alreadyUsed) {
            throw new \Exception('This helping method has already been used.');
        }

        $team->usedHelpingMethods()->attach($helpingMethodId);

        $team->load(['avatar', 'usedHelpingMethods', 'game']);

        return $team->game;
    }

    public function getGameBoard(Game $game): Game
    {
        $game->load([
            'teams.usedHelpingMethods',
            'questions.category'
        ]);

        return $game;
    }
}

