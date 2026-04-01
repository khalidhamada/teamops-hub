# TeamOps Hub

TeamOps Hub is a custom-table-first WordPress plugin for internal project and task coordination. It gives managers and team members a structured workspace for planning projects, assigning work, tracking progress, and collaborating on execution inside WordPress.

The plugin is designed for internal operations teams that want a practical delivery workspace today while keeping a solid architecture for future modules such as documents, notifications, reporting, and helpdesk workflows.

## Description

TeamOps Hub helps your organization:

- manage projects with owners, members, priorities, statuses, and milestones
- manage tasks inside projects with assignees, dates, workflow statuses, checklist items, and discussion
- give team members a focused workspace for updating work without exposing full admin management screens
- provide a front-end execution workspace with portfolio, overview, Kanban, tasks, and milestone views
- keep operational data in dedicated plugin tables instead of storing projects and tasks as WordPress posts

## Key Features

### Project Management

- Create and edit projects
- Set project owner, status, priority, dates, and notes
- Assign project team members
- Manage project milestones
- Track project progress and related work
- Restrict project visibility based on capability and membership rules

### Task Management

- Create tasks within projects
- Assign tasks to project members
- Use configurable workflow statuses
- Track due dates, estimated hours, and actual hours
- Add checklist-style subtasks
- Add task comments with `@mentions`
- Filter tasks by project, status, assignee, milestone, and due state
- Move tasks on the front-end Kanban board

### Team Workspaces

- Dashboard for project and task visibility
- Admin `My Work` area for assigned work
- Front-end workspace via `[teamops_hub_workspace]`
- Project-specific tabs for `Overview`, `Kanban`, `Tasks`, `Milestones`, and future file handling

## Who This Plugin Is For

- Internal IT and delivery teams
- Project managers and team leads
- Operations-focused WordPress administrators
- Team members who need a clean place to review and update assigned work

## Requirements

- WordPress 6.x or newer recommended
- PHP 7.4 or newer recommended
- A WordPress site where internal users can log in

## Installation

### Install From Plugin Folder

1. Download or clone this repository.
2. Copy the `teamops-hub` folder into `wp-content/plugins/`.
3. In WordPress admin, go to `Plugins`.
4. Activate `TeamOps Hub`.

### Install From ZIP

1. Download a ZIP of this plugin.
2. In WordPress admin, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file.
4. Click `Install Now`.
5. Activate `TeamOps Hub`.

## What Happens On Activation

When activated, TeamOps Hub:

- creates its custom database tables
- registers TeamOps roles and capabilities
- enables runtime upgrade checks for future schema and role updates

## First-Time Setup

After activation, recommended setup is:

1. Open `TeamOps Hub > Settings`.
2. Review the task workflow statuses and confirm they match your process.
3. Decide which users should be `TeamOps Managers` and which should be `TeamOps Members`.
4. Create your first project.
5. Assign project members.
6. Add milestones if needed.
7. Add tasks and assign them to project members.
8. Ask team members to use `My Work` or the front-end workspace.

## Front-End Workspace Setup

TeamOps Hub includes a front-end workspace shortcode for daily execution outside wp-admin.

### Shortcode

`[teamops_hub_workspace]`

### To Use It

1. Create a new WordPress page.
2. Add the shortcode `[teamops_hub_workspace]`.
3. Publish the page.
4. Make sure the user is logged in with TeamOps access.

The front-end workspace currently includes:

- portfolio dashboard
- project overview
- Kanban board
- task execution and task detail editing
- milestone snapshot

## User Roles and Permissions

### Administrators

- receive TeamOps manager-level capabilities
- can manage projects, tasks, reports, and settings

### TeamOps Managers

- can create and edit projects
- can create and edit tasks
- can manage settings and review operational dashboards

### TeamOps Members

- can access TeamOps
- can view assigned work
- can update their own task status and allowed workspace fields

## Typical Usage

### For Managers

1. Create a project.
2. Add the project owner and members.
3. Add milestones if needed.
4. Create tasks inside the project.
5. Assign tasks to project members.
6. Track progress from the dashboard, task views, and Kanban workspace.

### For Team Members

1. Open `TeamOps Hub > My Work` or the front-end workspace page.
2. Review assigned tasks.
3. Update statuses as work moves forward.
4. Complete checklist items.
5. Add comments or mentions where needed.

## Current Screens

- Dashboard
- Projects
- Tasks
- My Work
- Settings
- Reports placeholder
- Future Modules placeholder
- Front-end workspace

## Technical Notes

TeamOps Hub is built as a custom-table-first plugin. Core operational data is stored in dedicated tables rather than WordPress posts and post meta.

Current custom tables include:

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

## Current Limitations

This is still an early product foundation. At the moment:

- reporting is still lightweight
- admin list screens are custom implementations, not `WP_List_Table`
- attachments, dependencies, and time tracking are not yet implemented
- notification delivery is still limited and currently focused on in-app mention alerts
- broader live-site validation is still ongoing

## Roadmap Direction

Planned future expansion includes:

- richer notifications
- attachments and file workflows
- better reporting and KPI views
- activity and audit visibility
- REST/API integrations
- additional business modules such as documents and helpdesk

## Documentation

Project and architecture documentation is included in the repository:

- `AGENTS.md`
- `CONTEXT.md`
- `PLANS.md`
- `CHANGELOG.md`
- `PROJECT_PROGRESS.md`
- `EXECUTIVE.md`
- `docs/architecture-notes.md`
- `docs/database-schema-notes.md`
- `docs/migration-notes.md`
- `docs/future-roadmap.md`

## Frequently Asked Notes

- The plugin is multilingual ready through the `teamops-hub` text domain.
- The member experience is currently a hybrid of admin and front-end workspace flows.
- The architecture is intentionally modular so future modules can plug into the same foundation.

## License

This project is released under the MIT License. See `LICENSE`.
