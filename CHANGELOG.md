# CHANGELOG.md

## 2026-04-01

### Added

- Initial TeamOps Hub plugin scaffold
- Main plugin bootstrap with constants, autoloader, and lifecycle hooks
- Custom database schema manager for projects, project members, tasks, and activity logs
- Role and capability registration for managers and members
- Service layer for projects, tasks, permissions, validation, activity logging, and notifications stub
- Repository layer for project and task data access
- Admin pages for dashboard, projects, tasks, member workspace, settings, and placeholders
- Basic admin CSS and JS assets
- Documentation set including README, architecture notes, schema notes, migration notes, roadmap, and AGENTS guide
- Batch 1 workflow foundations for configurable task statuses and project milestones
- Batch 2 workflow foundations for task subtasks and checklist progress
- Batch 3 workflow foundations for task comments, `@mentions`, and in-app mention notifications
- Hybrid front-end workspace via shortcode for daily task execution

### Changed

- Added runtime schema/role upgrade checks during plugin boot
- Improved admin UX with success notices and project progress summary
- Display task project names in task list instead of raw project IDs when available
- Hardened project and task visibility rules so record access is enforced in services, not only by the admin UI
- Added project and task validation feedback for missing required fields, invalid date ranges, and invalid task assignees
- Upgraded project and task screens with richer filters, summary cards, responsive table wrappers, and full-width form controls
- Restricted task assignees to the selected project team plus the project owner, with client-side filtering and server-side validation
- Replaced hardcoded task statuses with database-backed workflow statuses managed from Settings
- Added milestone management to project screens and milestone-aware task filtering/forms
- Updated overdue/upcoming logic and completion handling to respect closed workflow statuses instead of a hardcoded `done` assumption
- Added a `task_subtasks` workflow table plus repository/service support for checklist-style child work
- Extended task detail and member workspace screens with checklist progress, checklist item creation, and completion toggles
- Added project-screen milestone editing and stronger milestone save validation
- Added task discussion threads on task details and member work items
- Added persisted mention notifications with a `My Work` notification panel and mark-all-read action
- Added custom tables and services for task comments, comment mentions, and notifications
- Added a front-end `[teamops_hub_workspace]` shortcode for logged-in users to review work, update status, complete checklists, and participate in task discussions
- Refined the front-end workspace styling to be calmer, denser, and more theme-friendly for WordPress sites
- Reworked the front-end workspace into a true global filtered view so project filtering now scopes both header/project context and the task queue together
- Replaced text-based checklist state controls in the front-end workspace with checkbox-style task execution controls
- Added front-end task detail editing beyond status updates, with restricted member-safe fields and richer manager controls
- Added a front-end Kanban board with drag-and-drop workflow moves wired into the existing task status permissions
- Refined the front-end Kanban board to use full workspace width and subtle status-based color accents sourced from configured workflow statuses
- Added front-end asset cache-busting based on file modification time so workspace CSS/JS updates appear reliably during iterative testing
- Reorganized the front-end workspace into a portfolio-first dashboard with project-specific tabs for overview, Kanban, tasks, milestones, and future files
- Added an MIT `LICENSE` file and repository-ready publishing metadata in the README

### Verified

- PHP syntax lint passed across all plugin PHP files

### Not Yet Verified

- Live WordPress activation and admin runtime behavior
- Database migration from pre-status/milestone installs inside a real WordPress environment
- Database migration from pre-subtask installs inside a real WordPress environment
- Database migration from pre-comment/notification installs inside a real WordPress environment
