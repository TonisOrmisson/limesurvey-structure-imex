# Quota Hierarchical Export Format

## Overview
The quota export format follows a hierarchical structure similar to QuestionGroup → Questions, where Quotas have multiple QuotaMembers beneath them.

## Format Structure

### Headers
```
type, name, value, active, autoload_url, message-{language}, url-{language}, url_description-{language}
```

Where `{language}` represents all survey languages starting with the main language, then additional languages.

**Note**: The quota `action` field (termination action 1-4) is intentionally omitted from this format for simplicity. It can be added later if needed.

**Example Headers (for survey with en, de, fr):**
```
type, name, value, active, autoload_url, message-en, url-en, url_description-en, message-de, url-de, url_description-de, message-fr, url-fr, url_description-fr
```

## Row Types

### Quota Rows (type = "Q")
- **type**: "Q"
- **name**: `quota->name` (quota display name)
- **value**: `quota->qlimit` (quota limit number)
- **active**: `quota->active` (0 or 1)
- **autoload_url**: `quota->autoload_url` (0 or 1)
- **message-{lang}**: `quotals_message` for each language
- **url-{lang}**: `quotals_url` for each language
- **url_description-{lang}**: `quotals_urldescrip` for each language

### QuotaMember Rows (type = "QM")
- **type**: "QM"
- **name**: Question code (from `question->title`)
- **value**: Answer code (from `quota_member->code`) - for all question types including equations
- **active**: null/empty
- **autoload_url**: null/empty
- **message-{lang}**: null/empty (QuotaMembers don't have language-specific content - only Quotas do via QuotaLanguageSettings)
- **url-{lang}**: null/empty
- **url_description-{lang}**: null/empty

**Note**: Each QuotaMember (QM) refers to exactly ONE question. The value is always the specific output value that triggers the quota condition (stored in `quota_member->code`).

## Hierarchical Relationship
- Each QuotaMember (QM) row relates to the preceding Quota (Q) row
- Multiple QM rows can follow a single Q row
- Each Q row starts a new quota definition

## Example Export Data

```csv
type,name,value,active,autoload_url,message-en,url-en,url_description-en,message-de,url-de,url_description-de
Q,"Male Quota",100,1,0,"Sorry, the male quota has been reached.","","","Entschuldigung, die Männerquote wurde erreicht.","",""
QM,"gender","M",,,"","","","","",""
Q,"Age 18-25",50,1,1,"Quota reached. Redirecting...","https://example.com/thanks","Thank you page","Quote erreicht. Weiterleitung...","https://example.com/danke","Dankesseite"
QM,"age_group","1",,,"","","","","",""
Q,"Education Quota",75,1,0,"Education quota reached.","","","Bildungsquote erreicht.","",""
QM,"education","1",,,"","","","","",""
QM,"education","2",,,"","","","","",""
QM,"education","3",,,"","","","","",""
```

## Database Mapping

### Quota (Q) → Database Tables
- **type**: Fixed "Q"
- **name**: `lime_quota.name`
- **value**: `lime_quota.qlimit`
- **active**: `lime_quota.active`
- **autoload_url**: `lime_quota.autoload_url`
- **message-{lang}**: `lime_quota_languagesettings.quotals_message` (by language)
- **url-{lang}**: `lime_quota_languagesettings.quotals_url` (by language)
- **url_description-{lang}**: `lime_quota_languagesettings.quotals_urldescrip` (by language)

### QuotaMember (QM) → Database Tables
- **type**: Fixed "QM"
- **name**: `lime_questions.title` (via `lime_quota_members.qid`)
- **value**: `lime_quota_members.code` (for all question types - the output value that triggers the quota)
- **active**: null
- **autoload_url**: null
- **message-{lang}**: null
- **url-{lang}**: null
- **url_description-{lang}**: null

## Import Processing Logic

1. **Parse rows sequentially**
2. **When type="Q"**: Create new quota and language settings
3. **When type="QM"**: Add quota member to current quota
4. **Validation**: Ensure question codes exist, answer codes are valid
5. **Language handling**: Process all language columns for quota rows

## Advantages of This Format

1. **Familiar Structure**: Matches existing question export format
2. **Clear Hierarchy**: Visual grouping of quota and its members
3. **Language Support**: All languages in single export
4. **Extensible**: Easy to add more quota or member properties
5. **Human Readable**: Clear relationship between quotas and conditions