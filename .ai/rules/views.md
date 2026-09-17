---
paths:
  - resources/views/landing.blade.php
---

# Views

## The landing page at / is a copied build of a separate project
`/` renders resources/views/landing.blade.php, which is the `vite build --assetsDir landing` output of the separate React project wala_kalima_landing_page (its own repo); the hashed JS/CSS live in public/landing/. It is committed prebuilt on purpose: this app has no Node build step (public/build is gitignored, nothing else uses Vite), and pulling it into this app's Vite would add React/GSAP/Lenis dependencies plus a build on deploy.

Never hand-edit public/landing/* or switch the view to @vite/asset(): change the source project, rebuild, then replace public/landing/ and the two hashed tags in the view together. The bundle hardcodes root-relative /img/*.webp paths, so its images must stay in public/img/ under their original names. tests/Feature/LandingPageTest fails when the view points at a missing bundle or the bundle at a missing image.
