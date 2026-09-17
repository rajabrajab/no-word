---
paths:
  - 'app/Exports/**'
---

# Exports

## Question sheet headings are the contract with the importer
The question templates (QuestionsTemplateSheet, CategoryBulkQuestionsTemplateExport) head their columns with the panel labels an admin reads — `__('panel.excel_*')`, `__('panel.bulk_excel_*_column')` — never the database column names. The importers under app/Services (QuestionsExcelImporter, CategoryBulkQuestionsExcelImporter) validate an uploaded sheet by matching that same header row, in every locale, via BaseQuestionsExcelImporter::headerMatches().

So a heading change is a two-file change: edit the template AND the matching key list in the importer, or every previously downloaded sheet stops importing with "invalid header". Column order is load-bearing too — the importers read cells by 1-based index and split pictures by which side of the answer-media column they are anchored on.

Pictures are pasted into the sheet, not referenced by path: media_type is derived ('image') and is never a column an admin fills in.
