# AGENTS.md

## Purpose

This file is the operating guide for AI agents working in this repository. Use it to reduce rediscovery across sessions, preserve architectural intent, and keep implementation aligned with the product direction for TeamOps Hub.

TeamOps Hub is a custom-table-first WordPress plugin for internal IT team coordination. The MVP currently centers on project management and project-linked task management, but the architecture is intentionally platform-oriented so future modules can be added without rewriting the core.

## Repo Expectations

- Treat this plugin as a serious internal operations product, not a throwaway prototype.
- Prefer maintainability, security, scalability, and clarity over short-term shortcuts.
- Keep the architecture modular and future-ready.
- Follow WordPress coding standards and native WordPress patterns where they make the plugin stronger.
- Keep the plugin multilingual ready.
- Prefer admin-first UX for MVP unless there is a clear reason to introduce front-end behavior.

## Core Architecture Guardrails

- Use custom database tables for operational/business entities.
- Do not move projects or tasks into `wp_posts` / `wp_postmeta`.
- Keep SQL in repositories, business rules in services, and rendering in admin page classes/templates.
- Do not mix permissions, SQL, validation, and HTML in one class if it can be avoided.
- Preserve the modular split between:
  - core bootstrap/lifecycle
  - database/schema
  - services/business logic
  - module-specific code
  - admin UI
  - docs and migration notes
- Any new module should plug into the same patterns used for Projects and Tasks where practical.

## Current Source of Truth

Before making substantial changes, review the most relevant docs and code:

- `AGENTS.md`
  Purpose: operating rules, constraints, and session expectations
- `README.md`
  Purpose: installer/user-facing overview, setup, workflows, and current limitations
- `CONTEXT.md`
  Purpose: current project snapshot, architecture map, known limitations, and key references
- `PLANS.md`
  Purpose: live execution board for current priorities, next steps, and open risks
- `CHANGELOG.md`
  Purpose: human-readable record of meaningful engineering and product changes
- `PROJECT_PROGRESS.md`
  Purpose: milestone history and implementation progress over time
- `EXECUTIVE.md`
  Purpose: business framing and stakeholder-level positioning
- `docs/architecture-notes.md`
  Purpose: architectural intent and layering decisions
- `docs/database-schema-notes.md`
  Purpose: schema shape, indexing intent, and migration strategy
- `docs/migration-notes.md`
  Purpose: versioning and lifecycle expectations
- `docs/future-roadmap.md`
  Purpose: expected future modules and expansion direction
- `base-prompt.md`
  Purpose: original product/architecture brief for the plugin

Treat these docs as working memory unless the code clearly indicates they are stale.

## Working Docs

The following file set is the working memory system for this repository.

- `AGENTS.md`
  Purpose: operating rules, repo expectations, and decision guardrails
- `CONTEXT.md`
  Purpose: current project snapshot, architecture map, known limitations, and key references
- `PLANS.md`
  Purpose: live execution board for active priorities, next steps, and open risks
- `CHANGELOG.md`
  Purpose: human-readable record of meaningful product and engineering changes
- `PROJECT_PROGRESS.md`
  Purpose: longer-form implementation history and milestone log
- `EXECUTIVE.md`
  Purpose: business framing for executive users and stakeholders

Working-doc expectations:

- Keep these files current when meaningful changes are made
- Use them to avoid rediscovery and repeated architectural drift across sessions
- Prefer updating them alongside code changes instead of leaving repo memory stale
- If one of these files becomes inaccurate, correct it promptly

## Session Startup Policy

At the start of every new session for meaningful repo work:

- Read `AGENTS.md`
- Read `CONTEXT.md`
- Read `PLANS.md`
- Read `CHANGELOG.md`
- Read `README.md`
- Read `PROJECT_PROGRESS.md`
- Read `EXECUTIVE.md`
- Read `docs/architecture-notes.md`
- Read `docs/database-schema-notes.md`
- Read `docs/migration-notes.md`
- Read `docs/future-roadmap.md`
- Read `base-prompt.md` when product intent, scope, or architectural intent needs re-grounding

Before substantial implementation:

- Summarize the current project state briefly
- Identify the most relevant constraints and active priorities
- Use relevant installed global skills when the task clearly matches them
- Avoid re-asking questions that are already answered by the repo docs

## Current Project State

As of the current repo state:

- A first-pass plugin scaffold exists and is organized around a modular structure
- Custom tables are defined for projects, project members, tasks, and activity logs
- Services and repositories exist for Projects and Tasks
- Admin areas exist for Dashboard, Projects, Tasks, My Work, Reports placeholder, Future Modules placeholder, and Settings
- Roles/capabilities are registered for managers and members
- Runtime schema/role upgrade checks are in place
- The member experience is admin-first in MVP
- Notifications are a stub for future extensibility
- The repo now includes a dedicated memory/tracking doc set: `CONTEXT.md`, `PLANS.md`, `CHANGELOG.md`, `PROJECT_PROGRESS.md`, and `EXECUTIVE.md`

## Implementation Rules

- Use the existing bootstrap entry in `teamops-hub.php`
- Keep schema changes inside the schema manager and version them deliberately
- Use prepared queries and `$wpdb` safely
- Add nonce checks and capability checks for every write path
- Sanitize inputs and escape outputs consistently
- Reuse services before adding new direct repository access from UI layers
- Prefer incremental extension of existing patterns over introducing a second architecture
- Add documentation updates when architecture, schema, or workflow assumptions change

## Near-Term Priorities

- Harden and refine runtime behavior in a live WordPress environment
- Improve list screens and filtering, likely moving toward `WP_List_Table`
- Expand validation and error handling
- Improve member workflows and project visibility behavior
- Add richer reporting and audit visibility
- Prepare for comments, attachments, subtasks, and dependency support without breaking current tables/services

## Change Discipline

When making meaningful updates:

- Update docs if architecture, schema, or workflows change
- Update `CHANGELOG.md` for meaningful product/engineering changes
- Update `PLANS.md` when priorities, next steps, or risks change
- Update `CONTEXT.md` when the current-state summary becomes inaccurate
- Update `PROJECT_PROGRESS.md` when a milestone meaningfully advances
- Keep naming consistent with the existing `TeamOpsHub` namespace and directory layout
- Prefer small, traceable changes over broad rewrites
- Preserve future-module readiness when making MVP decisions

## End-Of-Session Checklist

Before ending any meaningful implementation session, quickly verify the following:

- `CHANGELOG.md` reflects meaningful product or engineering changes from the session
- `PLANS.md` reflects the latest active priorities, next steps, and open risks
- `CONTEXT.md` still matches the actual current repo state
- `PROJECT_PROGRESS.md` is updated if the work materially advanced a milestone
- `README.md` is updated if installer/user-facing behavior changed
- `docs/architecture-notes.md` is updated if architectural decisions or boundaries changed
- `docs/database-schema-notes.md` and `docs/migration-notes.md` are updated if schema or versioning changed
- Any important divergence between docs and code is resolved or explicitly noted

If a file does not need changes, that is fine, but it should be consciously checked.

## If Docs Conflict With Code

- Prefer code for current behavior
- Prefer docs for intended direction
- If a conflict is real, update the docs to match the implemented truth or note the divergence explicitly
