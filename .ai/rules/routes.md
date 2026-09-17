---
paths:
  - 'routes/**'
---

# Routes

## The public question page is bound by token, never by id
`/question/{question:qr_token}` is deliberately bound to the questions.qr_token column, not the id. The page is unauthenticated and its QR codes get printed onto cards, so an id in the URL let anyone who scanned one card walk /question/1, /question/2 … and read every question and its answer.

Do not "simplify" this back to `{question}`, and do not add getRouteKeyName() on Question to do it globally — Filament admin URLs still use the id, which is correct there because the panel is authenticated.

Two supporting pieces exist for the same reason and must stay:
- Question::booted() issues a 32-hex-char token (128 bits) on creating, so every path that makes a question — the Filament form, both Excel importers, the bulk page, factories — gets one.
- QrCodeService stores the image as `qr-codes/{token}.svg`, never `qr-codes/question-{id}.svg`. The public disk is listable, so an id-named code could be fetched in order and every token decoded straight back out of it. It also deletes the file it replaces.

`php artisan questions:refresh-qr-codes` redraws every code (and deletes orphaned files); `--new-tokens` additionally rotates the tokens, which invalidates anything already printed and therefore asks for confirmation.
