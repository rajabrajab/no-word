---
paths:
  - 'app/Filament/Resources/**'
---

# Resources

## Resolve upload media type by extension, not sniffed MIME
Livewire's TemporaryUploadedFile::getMimeType() sniffs the file buffer only — it never sees the filename. An .m4a written with an `isom`/`mp42` ftyp brand (ffmpeg, most Android recorders) is byte-identical to MP4 video at the header, so finfo returns `video/mp4` and voice notes got saved with media_type = 'video'.

The map and the resolution now live in App\Services\MediaTypeResolver (QuestionForm::resolveMediaType() is a thin delegate kept for the form). Extension first, sniffed MIME only as a fallback, and the file is opened only when the extension means nothing — don't go back to matching on getMimeType() alone. Filament stores uploads as `{ulid}.{original extension}`, so the extension survives on the saved path too.

Never branch on the raw media_type / answer_media_type column when rendering: rows predate the column or hold the old sniffed value. Read Question::questionMediaType() / answerMediaType(), which run MediaTypeResolver::reconcile() — the extension outranks the column, the column is the fallback, sniffing is last.
