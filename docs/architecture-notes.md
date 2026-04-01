# Architecture Notes

## Core Decisions

- Custom database tables are the primary storage layer for projects, project members, tasks, and activity logs.
- The plugin uses a modular structure so future business modules can register without rewriting the core bootstrap.
- Business logic is kept in services, while repositories focus on data access.
- Admin pages depend on services instead of direct SQL to keep workflows consistent.

## Layering

- Bootstrap layer: plugin entry file, autoloader, installer, lifecycle hooks.
- Database layer: schema manager and repository classes.
- Domain/services layer: permissions, validation, activity logging, project logic, task logic, notifications stub.
- Admin UI layer: dashboard, projects, tasks, member workspace, settings, placeholders.
- Front-end UI layer: shortcode-powered workspace for logged-in execution flows.
- Extensibility layer: event-style notification hook plus room for new module registration.

## Member Experience Strategy

The MVP started with restricted admin screens because this kept capability checks, data access, and maintenance simpler for an internal operations tool. The codebase now also includes a shortcode-powered front-end workspace that reuses the same services, preserving the hybrid direction without duplicating business logic.

## Runtime Hardening Notes

- Record visibility should be enforced in services, not only by hiding admin UI controls.
- Validation should happen before repository writes so admin pages can return actionable notices.
- Project membership is part of task assignment rules, so task assignees should be limited to the selected project team and owner.
- Configurable workflow metadata such as task statuses should live in dedicated tables/services instead of hardcoded arrays once it starts driving multiple screens and future Kanban behavior.
- Project planning structures such as milestones should extend the existing project/task model rather than introducing a parallel planning module.
