# Agent Instructions

<!-- lean-ctx -->
## lean-ctx

lean-ctx is active — the MCP tools replace native equivalents.
Full rules: LEAN-CTX.md (open on demand — do not auto-load).
<!-- /lean-ctx -->

# ASAS Investor Portal — Permanent Development Instructions

## Project Knowledge

The project has already been analyzed.

Reuse the existing project context.

Do NOT rediscover the architecture.

Do NOT reread the repository.

Read only the minimum required files.

Never scan unrelated folders.

Assume completed features are correct unless explicitly modifying them.

---

## Planning Mode

When the user requests a plan:

1. Analyze only the required files.
2. Briefly explain the implementation.
3. List affected files.
4. Wait for explicit approval.
5. Do not modify files until approved.

---

## Architecture

Preserve the existing architecture.

Use:

* Thin Controllers
* Form Requests
* Services
* Actions (`execute()`)
* Policies
* Enums
* Settings Service

Never move business logic into Controllers or Blade.

Always extend existing implementations.

Never redesign completed modules.

---

## Existing Components

Before creating any:

* Service
* Action
* Notification
* Enum
* Widget
* Component
* Helper
* Observer
* Middleware
* Policy
* Resource

Search for an existing implementation.

Reuse existing code whenever possible.

Create new files only when truly necessary.

Never duplicate functionality.

---

## UI

Investor Portal

* IBM Plex Sans Arabic
* RTL
* Indigo theme (`ip-*`)
* Dark Mode

Admin Panel

* Filament
* Tajawal
* Teal theme (`asas-*`)

Never mix both design systems.

---

## Security

Always preserve:

* Policies
* Relationship-scoped queries
* IDOR protection
* Signed URLs
* Roles & Permissions

Never bypass authorization.

---

## Performance

Avoid N+1 queries.

Reuse eager loading.

Reuse existing queries.

Reuse cache whenever possible.

---

## Notifications

Reuse:

* Laravel Notifications
* Existing Notification classes
* Horizon
* Existing Notification Center

Never create duplicate notification systems.

---

## Settings

All configurable values must use the Settings service.

Never hardcode configurable values.

Never modify `.env` unless explicitly requested.

---

## Token Optimization

Read only the minimum required files.

Never scan the repository.

Never inspect unrelated modules.

Reuse existing indexed context.

If lean-ctx is available, use it automatically.

Keep responses concise.

Never explain internal reasoning.

Never generate long reports.

Modify only files directly related to the requested feature.

---

## Validation

Default:

* Build Verification only.

Never run:

* PHPUnit
* Browser Testing
* QA
* Security Audit
* Performance Audit

Unless explicitly requested.

---

## Final Response

Always return only:

* Files Added
* Files Updated
* Build Status
* Notes (maximum two short lines)

Then stop completely.

Never continue to the next feature automatically.

