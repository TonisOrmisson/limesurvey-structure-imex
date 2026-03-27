# RemoteControl Plugin API

This plugin exposes API actions to LimeSurvey RemoteControl through:

- `list_plugin_api(sessionKey, pluginName = null)`
- `call_plugin_api(sessionKey, pluginName, action, payload = {}, context = {})`

## Security and Availability

The core RemoteControl plugin API is protected by a global kill switch:

- Global setting key: `rpc_plugin_api`
- Default: `Off` (`0`)
- UI path: Global settings -> Interfaces -> `Enable plugin API on RemoteControl`

When disabled, both methods return: `Error: Plugin API disabled`.

Each action also declares permission metadata (`remoteControlPermission`) and core validates permission **before** dispatching to plugin code.

For `StructureImEx` actions, required permission is:

- survey scope
- `surveycontent.read`
- survey id resolved from `payload.sid` / `payload.surveyId` (or same keys in `context`)

## Discovery

List actions for this plugin:

```json
{
  "method": "list_plugin_api",
  "params": ["<sessionKey>", "StructureImEx"],
  "id": 2
}
```

## Actions

### `list_group_items`

Returns group-level IMEX items only.

Input payload:

```json
{
  "sid": 225627,
  "language": "et"
}
```

### `list_group_question_items`

Returns top-level question IMEX items for one group.

Input payload:

```json
{
  "sid": 225627,
  "gid": 868,
  "language": "et"
}
```

### `list_questions_by_group`

Returns groups with top-level questions (no subquestions).

Input payload:

```json
{
  "sid": 225627,
  "language": "et"
}
```

### `get_question_structure`

Returns one question with subquestions and answers in IMEX-like row format.

Input payload (`sid` required for core permission checks):

```json
{
  "qid": 4380,
  "sid": 225627,
  "language": "et"
}
```

## Common Response Notes

Responses include IMEX-compatible data built with the same row builder used by Excel export:

- `imexHeader`
- `imexRow` objects
- localized values by `language` (fallback to survey base language)
