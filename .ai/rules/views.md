---
paths:
  - resources/views/landing.blade.php
  - 'app/Services/LandingShowcaseService.php'
  - 'app/Http/Controllers/Api/LandingController.php'
  - 'app/Http/Resources/LandingShowcaseResource.php'
---

# Views

## The landing page at / is a copied build of a separate project
`/` renders resources/views/landing.blade.php, which is the `vite build --assetsDir landing` output of the separate React project wala_kalima_landing_page (its own repo); the hashed JS/CSS live in public/landing/. It is committed prebuilt on purpose: this app has no Node build step (public/build is gitignored, nothing else uses Vite), and pulling it into this app's Vite would add React/GSAP/Lenis dependencies plus a build on deploy.

Never hand-edit public/landing/* or switch the view to @vite/asset(): change the source project, rebuild, then replace public/landing/ and the two hashed tags in the view together. The bundle hardcodes root-relative /img/*.webp paths, so its images must stay in public/img/ under their original names. tests/Feature/LandingPageTest fails when the view points at a missing bundle or the bundle at a missing image.

## The landing page's sample data comes from GET /api/landing
The categories, demo-board questions and country strips the page shows are real rows, served by LandingController -> LandingShowcaseService -> LandingShowcaseResource. Three blocks: `board` (3 categories that have a question in every one of Category::SCORES, one random question per tier), `marquee` (distinct category names) and `countries` (active countries with categories). Picks are random per request and respect the X-Locale language filter, so the payload is not cacheable as-is.

Two things constrain any change to it:

- **Everything is a list, never a map keyed by score.** JsonResource::removeMissingValues() runs array_values() over any nested array whose keys are all numeric, so `{200: [...], 400: [...]}` silently comes out renumbered `0, 1, 2`. CategoryResource's `answer_times` works around the same trap.
- **Each block falls back to the demo constants in LandingShowcaseService** when the database cannot fill it, as unsaved Category/Question/Country models so the resource keeps one shape to render. An unseeded or partly-filled database must still render a finished page; the blocks fall back one at a time.

The bundle calls it through `src/showcase.js` in the wala_kalima_landing_page project: one shared request, and each of BoardDemo / Marquee / Modes keeps its old hand-written array as the value it starts on and stays on if the request fails. That is what makes `npm run dev` work with no Laravel behind it, so do not delete those arrays when editing those components.

Two shapes are cut to fit the design rather than to fit the data: `COUNTRIES` is 3 because the bundle lays the country picker out as one row of buttons (the live database has 18 countries and would wrap it into a wall), and the board deduplicates category names before picking, because every country runs its own "الفنون" and three rows drawn at random are often the same word twice.

Note it hands a few real question/answer pairs to an unauthenticated caller, which is the one place that happens by design (contrast the token-bound question page in .ai/rules/routes.md). Keep the sample small; if harvesting ever matters, cache the payload for a few minutes rather than widening it.
