# CONTEXT.md

## Project Snapshot

TeamOps Hub is a custom-table-first WordPress plugin for internal IT team coordination and collaboration. The current product foundation is implemented around two modules:

- Projects
- Tasks

The architecture is intentionally platform-oriented so future modules such as helpdesk, documents, notifications, reporting, and audit trails can reuse the same bootstrap, permissions, database strategy, UI patterns, and service structure.

## Current Architecture Map

- `teamops-hub.php`
  Main plugin bootstrap, constants, autoloader registration, activation/deactivation hooks
- `includes/Core`
  Core bootstrap, installer, runtime upgrade checks
- `includes/Database`
  Custom-table schema management and migration entry point
- `includes/Services`
  Business logic and cross-cutting services such as permissions, validation, activity logging, notifications, task statuses, and milestones
- `includes/Repositories`
  Shared repository base helpers
- `includes/Modules/Projects`
  Project-specific module wiring, repository, admin UI
- `includes/Modules/Tasks`
  Task-specific module wiring, repositories, and admin UI
- `includes/Admin`
  Shared admin menu, dashboard, member area, settings
- `includes/Frontend`
  Shortcode-powered front-end workspace for logged-in team members and managers
- `assets`
  Shared admin CSS and lightweight JS
- `docs`
  Architecture, schema, migration, and roadmap documentation

## Current Functional State

- Plugin bootstrap and autoloading are in place
- Activation creates custom tables and roles/capabilities
- Runtime checks can apply schema/role upgrades after deployment
- Projects module supports manager/admin CRUD, member assignment, owner-aware visibility, filtering, project health summaries, and add/edit milestone management
- Tasks module supports manager/admin CRUD, member status updates, richer filtering, project-team-aware assignee validation, configurable workflow statuses, milestone assignment, checklist-style subtasks, and task comments with mentions
- Dashboard shows visibility-aware counts and task summaries
- Member `My Work` area shows assigned projects and tasks and now returns clearer task-status labels and notices
- Front-end workspace shortcode now follows a two-level structure: a portfolio dashboard for all projects, then a selected-project workspace with tabs for overview, Kanban, tasks, milestones, and future files
- Settings page manages member-view strategy and task workflow statuses
- Reports and future-modules screens are placeholders

## Current Database Tables

- `teamops_hub_projects`
- `teamops_hub_project_members`
- `teamops_hub_tasks`
- `teamops_hub_activity_log`
- `teamops_hub_task_statuses`
- `teamops_hub_milestones`
- `teamops_hub_task_subtasks`
- `teamops_hub_task_comments`
- `teamops_hub_task_comment_mentions`
- `teamops_hub_notifications`

## Current Capability Model

- `administrator`
  Receives TeamOps manager-level capabilities
- `teamops_manager`
  Can manage projects, tasks, reports, and settings
- `teamops_member`
  Can access TeamOps and update own assigned task statuses

## Known Limitations

- Admin list screens are still custom tables, not `WP_List_Table`
- Live runtime validation is in progress, and recent fixes addressed a recursive task-loading bug plus milestone workflow gaps discovered during admin testing
- The hybrid direction is now real rather than planned: front-end execution reuses the same services as the admin workspace
- No automated tests are implemented yet
- Attachments, dependencies, and time-tracking entities are not implemented yet
- No REST API endpoints yet
- Status, milestone, checklist, comment, and mention-notification foundations are in place, and the front-end workspace now has a clearer portfolio-to-project information architecture plus Kanban interactions, but attachments and broader notification triggers still need implementation
- Reporting is intentionally lightweight

## Key References

- `AGENTS.md`
- `README.md`
- `docs/architecture-notes.md`
- `docs/database-schema-notes.md`
- `docs/migration-notes.md`
- `docs/future-roadmap.md`
- `base-prompt.md`

## Practical Guidance

- Prefer extending current services and repositories over adding parallel patterns
- Keep custom tables as the source of truth for operational entities
- Update docs when schema, architecture, or workflow assumptions change
- Treat this file as the fast current-state summary for future sessions
