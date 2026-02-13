<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Game;
use App\Models\Question;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function createGame(array $data, int $userId): Game
    {
        $user = \App\Models\User::findOrFail($userId);

        if (!$user->hasRemainingGames()) {

            if ($user->has_used_default_game) {
                throw new \Exception('You have no remaining games in your subscription. Please subscribe to a package.');
            }

            $isDefault = true;

        } else {
            $isDefault = false;
        }

        DB::beginTransaction();

        try {
            $game = Game::create([
                'name' => $data['name'] ?? null,
                'user_id' => $userId,
                'status' => 'active',
            ]);

            if (!$isDefault) {
                $user->decrementGamesRemaining();
            } else {
                $user->update(['has_used_default_game' => true]);
            }

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

    public function useHelpingMethod(int $teamId, int $helpingMethodId): bool
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

        return true;
    }

    public function getGameBoard(Game $game): Game
    {
        $game->load([
            'teams.usedHelpingMethods',
            'teams.avatar',
            'questions.category'
        ]);

        return $game;
    }

    public function resetGame(Game $game, array $data): Game
    {
        DB::beginTransaction();

        try {
            $teams = $game->teams()->orderBy('id')->get();

            if ($teams->count() > 0) {
                $team1 = $teams->first();
                $team1->update([
                    'name' => $data['team1']['name'],
                    'players_number' => $data['team1']['players_number'],
                    'avatar_id' => $data['team1']['avatar_id'] ?? null,
                    'score' => 0,
                ]);

                $team1->usedHelpingMethods()->detach();
            }

            if ($teams->count() > 1) {
                $team2 = $teams->skip(1)->first();
                $team2->update([
                    'name' => $data['team2']['name'],
                    'players_number' => $data['team2']['players_number'],
                    'avatar_id' => $data['team2']['avatar_id'] ?? null,
                    'score' => 0,
                ]);

                $team2->usedHelpingMethods()->detach();
            }

            $game->load(['teams.usedHelpingMethods', 'teams.avatar', 'questions.category']);

            DB::commit();

            return $game;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to reset game: ' . $e->getMessage());
        }
    }

    public function createRandomGame(array $data, int $userId): Game
    {
        $user = \App\Models\User::findOrFail($userId);

        if (!$user->hasRemainingGames()) {

            if ($user->has_used_default_game) {
                throw new \Exception('You have no remaining games in your subscription. Please subscribe to a package.');
            }

            $isDefault = true;

        } else {
            $isDefault = false;
        }

        DB::beginTransaction();

        try {
            $game = Game::create([
                'name' => $data['name'] ?? null,
                'user_id' => $userId,
                'status' => 'active',
            ]);

            if (!$isDefault) {
                $user->decrementGamesRemaining();
            } else {
                $user->update(['has_used_default_game' => true]);
            }

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

            $randomCategories = Category::whereIn('country_id', $data['countries'])
                ->inRandomOrder()
                ->limit(6)
                ->pluck('id')
                ->toArray();

            if (!empty($randomCategories)) {
                $game->categories()->attach($randomCategories);
            }

            $selectedQuestionIds = [];
            $scores = [200, 400, 600];

            foreach ($randomCategories as $categoryId) {
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
            throw new \Exception('Failed to create random game: ' . $e->getMessage());
        }
    }

    public function replaceQuestion(Game $game, int $questionId): array
    {
        DB::beginTransaction();

        try {
            $questionToReplace = DB::table('game_questions')
                ->join('questions', 'game_questions.question_id', '=', 'questions.id')
                ->where('game_questions.game_id', $game->id)
                ->where('game_questions.question_id', $questionId)
                ->select('game_questions.id as pivot_id', 'game_questions.question_id', 'questions.category_id', 'questions.score')
                ->first();

            if (!$questionToReplace) {
                throw new \Exception('Question not found in this game.');
            }

            $newQuestionId = DB::selectOne("
                SELECT id
                FROM questions
                WHERE category_id = ?
                AND score = ?
                AND id != ?
                ORDER BY RAND()
                LIMIT 1
            ", [
                $questionToReplace->category_id,
                $questionToReplace->score,
                $questionToReplace->question_id
            ]);

            if (!$newQuestionId) {
                throw new \Exception('No alternative question found with the same category and level.');
            }

            DB::table('game_questions')
                ->where('id', $questionToReplace->pivot_id)
                ->update(['question_id' => $newQuestionId->id]);


            $game->load([
                'teams.usedHelpingMethods',
                'teams.avatar',
                'questions.category'
            ]);

            $newQuestion = $game->questions()
                ->where('questions.id', $newQuestionId->id)
                ->with('category')
                ->first();

            if (!$newQuestion) {
                $newQuestion = Question::with('category')->findOrFail($newQuestionId->id);
            }

            DB::commit();

            return $newQuestion;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to replace question: ' . $e->getMessage());
        }
    }
}

