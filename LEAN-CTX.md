<!-- lean-ctx-owned: PROJECT-LEAN-CTX.md v1 -->
<!-- lean-ctx-rules -->
<!-- version: 1 -->

CRITICAL: ALWAYS use lean-ctx tools instead of native equivalents. This is NOT optional.

MANDATORY MAPPING:
• Read/cat -> ctx_read(path, mode)
• Glob/find -> ctx_glob(pattern)
• Shell/bash -> ctx_shell(command)
• Grep -> ctx_search(pattern, path)
• ls/find -> ctx_tree(path, depth)

NEVER use native Read/Grep/Shell/Glob when ctx_* equivalents are available.

Tool selection by intent:
• Understand code / find answers / before editing -> ctx_compose (call FIRST)
• Read a file -> ctx_read(path, mode=signatures|map|full)
• Find a symbol by name (exact) -> ctx_symbol
• Search code by pattern (fuzzy) -> ctx_search
• Search by meaning (concepts) -> ctx_semantic_search
• Find files by pattern (glob) -> ctx_glob
• Project structure -> ctx_tree
• Who calls this / call graph -> ctx_callgraph
• Session state / memory -> ctx_session / ctx_knowledge

Anti-patterns — do NOT:
• Chain ctx_search -> ctx_read -> ctx_symbol — one ctx_compose replaces all three
• Grep for symbol definitions — ctx_symbol is faster + more precise
• Use ctx_read(mode=full) for orientation — use mode=signatures
• Use ctx_callgraph or ctx_graph for const/static/variable references — they track
function call edges and file-level deps only. Use grep or ctx_compose instead

PARALLEL tool calls: fire independent calls in the SAME turn — don't sequence them.
One turn with 5 parallel ctx_read calls completes faster than 5 sequential turns.
ctx_compose bundles multiple lookups into one call; for anything it doesn't
cover, batch independent reads/searches together.

Auto: preload/dedup/compress run in background. ctx_session=memory, ctx_knowledge=facts, ctx_semantic_search=meaning search, ctx_shell raw=true=uncompressed. Details: LEAN-CTX.md

CEP v1: 1.ACT FIRST 2.DELTA ONLY (Fn refs) 3.STRUCTURED (+/-/~) 4.ONE LINE PER ACTION 5.QUALITY ANCHOR

OUTPUT: never echo tool output, no narration comments, show only changed code.

TOOL PREFERENCE (END): ctx_compose>chain ctx_read>Read ctx_shell>Shell ctx_search>Grep ctx_glob>Glob ctx_tree>ls | Edit/Write/Delete=native
OUTPUT STYLE: concise
- Bullet points over paragraphs
- Skip filler words and hedging ("I think", "probably", "it seems")
- 1-sentence explanations max, then code/action
- No repeating what the user said
<!-- /lean-ctx-rules -->

# Project-Specific Rules

These rules apply only to the ASAS Investor Portal project.

---

## Project Context

The project has already been analyzed and indexed.

Reuse the existing indexed project context.

Do not rediscover the architecture.

Do not reread the repository.

Assume completed features are correct unless the current task explicitly modifies them.

---

## lean-ctx Usage

Always use lean-ctx as the primary source of project knowledge.

Before editing any feature:

1. Call `ctx_compose` first.
2. Open only the files returned by `ctx_compose`.
3. Read signatures before reading full files.
4. Prefer semantic search over manual searching.
5. Reuse previous context whenever possible.

Never manually scan the repository.

Never browse folders for orientation.

Never read unrelated files.

---

## File Reading

Prefer:

* ctx_compose
* ctx_symbol
* ctx_semantic_search
* ctx_read(signatures)

Use `ctx_read(full)` only when actually modifying the file.

Never read entire files for orientation.

Never open more files than necessary.

Batch independent reads whenever possible.

---

## Existing Architecture

Always preserve the existing architecture.

Reuse:

* Controllers
* Form Requests
* Services
* Actions
* Policies
* Notifications
* Enums
* Settings
* Widgets
* Resources
* Components

Never redesign completed modules.

Never duplicate existing logic.

Prefer extending existing implementations.

Create new classes only when absolutely necessary.

---

## UI

Investor Portal

* IBM Plex Sans Arabic
* RTL
* Indigo theme (`ip-*`)
* Dark mode

Admin Panel

* Filament
* Tajawal
* Teal theme (`asas-*`)

Never mix both design systems.

Always reuse existing Blade components.

Always reuse CSS tokens.

---

## Security

Always preserve:

* Policies
* Authorization
* Relationship scoped queries
* Signed URLs
* Existing Roles
* Existing Permissions

Never bypass authorization.

Never expose private files.

---

## Performance

Avoid N+1 queries.

Reuse eager loading.

Reuse cached values.

Reuse existing queries.

Avoid unnecessary database queries.

Prefer grouped SQL queries.

Keep business logic efficient.

---

## Notifications

Reuse:

* Laravel Notifications
* Existing Notification classes
* Existing Notification Center
* Horizon
* Queue system

Never create a second notification architecture.

---

## Settings

All configurable values must use the existing Settings service.

Never hardcode configurable values.

Never modify `.env` unless explicitly requested.

---

## Token Optimization

Minimize token usage.

Reuse indexed context.

Never re-analyze completed features.

Never generate unnecessary explanations.

Never produce long reports.

Modify only the files required for the current task.

Keep responses concise.

---

## Planning Mode

When the user requests a plan:

* Analyze only the required files.
* Explain the implementation briefly.
* List affected files.
* Wait for explicit approval.
* Do not edit files before approval.

---

## Validation

Default validation:

* Build Verification only.

Do NOT run:

* PHPUnit
* Browser Testing
* QA
* Security Audit
* Performance Audit

Unless explicitly requested.

Do not modify test files.

Do not create tests unless explicitly requested.

---

## Response Format

Keep responses short.

Return only:

* Files Added (if any)
* Files Updated
* Build Status
* Notes (maximum two short lines)

Stop immediately after completing the requested feature.

Never continue to the next feature automatically.

Never implement features outside the requested scope.
