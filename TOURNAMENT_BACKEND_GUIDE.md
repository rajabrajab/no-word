# Tournament Backend Guide

---

## What is `position`?

`position` is a **zero-based index** of a match within its round.

```
Round 1:  match at position 0 | match at position 1 | match at position 2 | match at position 3
Round 2:  match at position 0 | match at position 1
Round 3:  match at position 0   ← Final
```

It serves two purposes:

1. **Visual placement** — the client uses it to draw the bracket on screen (which column and row a match card appears in).
2. **Winner routing** — when a match ends, the backend uses `position` to know exactly which match in the next round the winner belongs to.

---

## The Two Formulas

These are the only two formulas the backend ever needs:

```
next_match_position = current_position / 2   (integer division)
is_first_team       = current_position % 2 == 0
```

### What they mean

| current_position | next_match_position | is_first_team | Winner fills slot |
|:---:|:---:|:---:|:---:|
| 0 | 0 | true  | team1 of match 0 in next round |
| 1 | 0 | false | team2 of match 0 in next round |
| 2 | 1 | true  | team1 of match 1 in next round |
| 3 | 1 | false | team2 of match 1 in next round |
| 4 | 2 | true  | team1 of match 2 in next round |
| 5 | 2 | false | team2 of match 2 in next round |
| 6 | 3 | true  | team1 of match 3 in next round |
| 7 | 3 | false | team2 of match 3 in next round |

---

## How the Backend Generates Rounds

### Step 1 — Validate input

```
teams.length MUST equal size (4, 8, or 16)
```

Reject the request otherwise.

---

### Step 2 — Calculate round count and match count

| size | total_rounds | matches per round              | total matches |
|:----:|:------------:|:------------------------------:|:-------------:|
| 4    | 2            | R1=2, R2=1                     | 3             |
| 8    | 3            | R1=4, R2=2, R3=1               | 7             |
| 16   | 4            | R1=8, R2=4, R3=2, R4=1        | 15            |

Formula:
```
total_rounds       = log2(size)
matches_in_round_N = size / 2^N
total_matches      = size - 1
```

---

### Step 3 — Build Round 1 (seed the teams)

Pair teams sequentially by index:

```
match position 0 → teams[0] vs teams[1]
match position 1 → teams[2] vs teams[3]
match position 2 → teams[4] vs teams[5]
match position 3 → teams[6] vs teams[7]
...and so on
```

Every Round 1 match gets:
```json
{
  "id":       "match_1_{position}",
  "game_id":  "<linked game session id>",
  "position": 0,
  "round":    1,
  "status":   "pending",
  "team1":    { ...team object },
  "team2":    { ...team object },
  "winner":   null
}
```

---

### Step 4 — Build Rounds 2 to N (empty slots)

For every round after Round 1, create empty match slots.
Both `team1` and `team2` are `null` — they will be filled later when winners arrive.

```json
{
  "id":       "match_{round}_{position}",
  "game_id":  "<linked game session id>",
  "position": 0,
  "round":    2,
  "status":   "pending",
  "team1":    null,
  "team2":    null,
  "winner":   null
}
```

> A `game_id` can be pre-created for every match at this step (so game sessions exist in advance),
> or created lazily when the match becomes ready (`team1` and `team2` are both filled).

---

### Step 5 — Persist and respond

Store all rounds and matches in the database, then return the full bracket as the response.

---

## Linking a Game to a Match (Lazy `game_id`)

`game_id` starts as `null` on every match slot.
It is only set when:
1. Both `team1` and `team2` are filled (match is **ready**)
2. The players open the match and create a game session on the game-creation screen

The client checks `game_id == null` → shows the game-creation screen → user creates the game → client calls this endpoint to link it:

```
PATCH /api/tournaments/{tournament_id}/matches/{match_id}/game-id
```

**Request body:**
```json
{
  "game_id": "game_1005"
}
```

**Validation the backend must do:**
```
1. match.team1 != null AND match.team2 != null   → match must be ready
2. match.status == "pending"                     → not already completed
3. match.game_id == null                         → not already linked
4. game_id exists in the games table             → valid game session
```

**Response — the updated match object:**
```json
{
  "id": "match_2_0",
  "game_id": "game_1005",
  "position": 0,
  "round": 2,
  "status": "pending",
  "team1": { "id": "team_1", "name": "الأسد",  "avatar_url": null, "avatar_id": 4, "score": 0 },
  "team2": { "id": "team_4", "name": "التنين", "avatar_url": null, "avatar_id": 1, "score": 0 },
  "winner": null
}
```

> After this call the client navigates into the game session using the returned `game_id`.

---

## How the Backend Processes a Winner

When the client sends:
```
POST /tournaments/{tournament_id}/matches/{match_id}/winner
{ "winner_id": "team_1" }
```

The backend does the following:

```
1. Find the match by match_id
2. Validate: match.status == "pending" and winner_id is team1 or team2
3. Update the match:
      match.winner = winner
      match.status = "completed"

4. If match.round < total_rounds (not the final):
      next_round           = match.round + 1
      next_match_position  = match.position / 2        ← integer division
      is_first_team        = match.position % 2 == 0

      Find the match at (round=next_round, position=next_match_position)

      If is_first_team:
          next_match.team1 = winner
      Else:
          next_match.team2 = winner

5. If match.round == total_rounds (this IS the final):
      tournament.champion    = winner
      tournament.is_completed = true

6. Save changes and return the updated full bracket
```

---

## Full 8-Team Example — Who meets who

```
ROUND 1 (ربع النهائي)          ROUND 2 (نصف النهائي)      ROUND 3 (النهائي)
─────────────────────          ──────────────────────      ─────────────────
pos=0: team1 vs team2  ──┐
                          ├──► pos=0: W(0) vs W(1) ──┐
pos=1: team3 vs team4  ──┘                             │
                                                        ├──► pos=0: CHAMPION
pos=2: team5 vs team6  ──┐                             │
                          ├──► pos=1: W(2) vs W(3) ──┘
pos=3: team7 vs team8  ──┘
```

W(n) = winner of Round 1 match at position n

---

## Round Names (Arabic)

| round == total_rounds     | "النهائي"      |
|---------------------------|----------------|
| round == total_rounds - 1 | "نصف النهائي"  |
| round == total_rounds - 2 | "ربع النهائي"  |
| round == total_rounds - 3 | "دور الـ 16"   |
