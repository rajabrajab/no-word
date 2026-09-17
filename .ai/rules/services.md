---
paths:
  - 'app/Services/**'
---

# Services

## The questions import upserts by the exported id, and never blanks media
QuestionsExcelImporter accepts two sheets and tells them apart by the header row: the blank template (7 columns, no id) and the sheet from "Export questions to Excel" (9 columns, leading with the id).

The id column is the update key. A row that keeps its exported id edits that question in place; a row with a blank id adds a new one. An id that matches nothing is reported and skipped — never created with a forced id, which would collide with the auto-increment.

Two rules exist because re-uploading an export must be safe:

1. A blank picture cell is NOT a removal. The export draws embeddable images onto the sheet and writes a path only for media it cannot draw, so media is replaced only when the row actually carries one (a pasted drawing, or a path that still names a file on the public disk). Otherwise the stored media is left alone.
2. Fields are compared as strings before writing, so a round-tripped export reports `unchanged` instead of rewriting every row and bumping 400-odd updated_at values.

The export carries both category id and category name; if they resolve to different categories the row is refused rather than guessed at, since editing one and leaving the other stale would move the question to whichever column won.

Verified on the dev database: exporting all 432 questions, editing one row and re-importing gives created 0 / updated 1 / unchanged 431 / skipped 0, with every other row byte-identical.
