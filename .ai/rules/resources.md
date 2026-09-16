---
paths:
  - 'app/Filament/Resources/**'
---

# Resources

## Resolve upload media type by extension, not sniffed MIME
Livewire's TemporaryUploadedFile::getMimeType() sniffs the file buffer only — it never sees the filename. An .m4a written with an `isom`/`mp42` ftyp brand (ffmpeg, most Android recorders) is byte-identical to MP4 video at the header, so finfo returns `video/mp4` and voice notes got saved with media_type = 'video'.

QuestionForm::resolveMediaType() therefore maps the file extension first (MEDIA_TYPES_BY_EXTENSION) and only falls back to the MIME prefix. Reuse it for any new media upload; don't go back to matching on getMimeType() alone. Filament stores uploads as `{ulid}.{original extension}`, so the extension survives on the saved path too.
