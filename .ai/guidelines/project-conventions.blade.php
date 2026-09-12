# NoWord Project Conventions

This is an **API-first Laravel app** (Sanctum-authenticated JSON API for a quiz/tournament game)
with a **Filament 4 admin panel**. Follow these project rules in addition to the framework guidelines above.

## API responses — always use the response macros

Never return `response()->json()` directly from an API controller. Two macros are registered in
`app/Providers/ResponseServiceProvider.php`:

- Success: `return response()->sendResponse($data, ResponseMessages::INDEX_SUCCESS);`
- Failure: `return response()->sendError(HttpCodes::NOT_FOUND, ResponseMessages::NOT_FOUND, $errors);`

Rules:
- Messages come from `App\Constants\ResponseMessages` constants — add a new constant instead of
  inlining a string literal.
- Codes come from `App\Http\Helpers\HttpCodes` (note: this app uses non-standard codes such as
  `VALIDATION_ERROR = 403`, `MODEL_NOT_FOUND = 900`, `TOKEN_NOT_FOUND = 980`). Never invent a raw code.
- The envelope is `{state, code, message, data|errors}`. Mobile clients depend on this shape — do not
  change the envelope keys.
- Wrap payloads in an `App\Http\Resources\*Resource` rather than returning models or arrays directly.

## Request lifecycle & layering

`routes/api.php` -> FormRequest -> thin Controller -> Service -> Model/Builder -> API Resource

- Validation belongs in an `App\Http\Requests\*Request` class, never inline in the controller.
- Business logic belongs in `App\Services\*` (`GameService`, `TournamentService`, `AuthService`,
  `CategoryBulkQuestionsExcelImporter`, `QrCodeService`). Controllers should read as orchestration only.
- Protected routes use the `auth:sanctum` middleware; keep new authenticated routes inside the existing
  `Route::middleware('auth:sanctum')->group(...)` block in `routes/api.php`.

## Models

- Application models extend `App\Models\BaseModel`, which sets `$guarded = []` and hides
  `created_at`/`updated_at`/`deleted_at`. Because models are unguarded, **always pass a validated,
  explicitly-keyed array to `create()`/`update()`** — never a raw `$request->all()`.
- Reusable query logic goes in `App\Models\Builders\BaseBuilder` (exposes `search()` reading
  `request()->search`), wired up per model via `newEloquentBuilder()`. Model-specific filters stay as
  local scopes (`scopeByCountry`, etc.).
- Prefer `SoftDeletes` for user-facing catalogue models, matching `Category`, `Question`, and friends.

## Filament 4 admin panel

- Resources follow Filament 4's split layout — do not use the Filament 3 single-file resource shape:
  `app/Filament/Resources/<Plural>/<Singular>Resource.php` plus `Pages/`, `Schemas/<Singular>Form.php`,
  and `Tables/<Plural>Table.php`.
- Forms receive a `Filament\Schemas\Schema`; tables receive a `Filament\Tables\Table`.
- Icons use the `Filament\Support\Icons\Heroicon` enum, not string icon names.
- Every admin-facing label goes through `__('panel.*')` — see the resource label overrides
  (`getNavigationLabel`, `getModelLabel`, `getPluralModelLabel`).

## Localization (bilingual, RTL)

- The panel ships English and Arabic: `lang/en/panel.php` and `lang/ar/panel.php`.
- Any new admin label requires a key added to **both** files. Never hardcode user-facing text.
- Arabic is RTL — keep layouts direction-agnostic.

## Excel import/export

- Exports live in `app/Exports`, imports in `app/Imports`, built on `maatwebsite/excel`.
- Bulk import orchestration belongs in `App\Services\CategoryBulkQuestionsExcelImporter`, not in the
  Filament page class.

## Known issues — do not copy these patterns

- `ResponseServiceProvider` calls `env('FULL_SYSTEM_DEBUG')` at runtime. That returns `null` once
  `config:cache` has run. New code must read from `config()` instead; move this to a config entry if
  you touch that provider.

## Before finishing

- Run `vendor/bin/pint --dirty` (Laravel preset, no custom `pint.json`).
- Run `php artisan test` — tests use in-memory SQLite (see `phpunit.xml`), so they never touch the
  MySQL dev database.
