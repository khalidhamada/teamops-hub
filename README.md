# TeamOps Hub

TeamOps Hub is a WordPress plugin for internal team coordination in IT companies. It gives managers and team members a central place to organize projects, assign work, track task progress, and monitor what is due next.

The current release is still early, but it now goes beyond bare MVP CRUD. Projects and tasks have stronger validation, visibility controls, filtering, and daily-operational UX while preserving a foundation designed for future expansion into helpdesk, documents, notifications, reporting, and broader internal operations workflows.

## Who It Is For

- IT companies managing internal delivery teams
- project managers coordinating projects and assignments
- team leads tracking project and task progress
- administrators who want a structured internal operations plugin inside WordPress
- team members who need a simple view of assigned work and status updates

## What The Plugin Does

TeamOps Hub currently helps your team:

- create and manage projects
- assign team members to projects
- create and manage tasks inside projects
- assign tasks to individual users
- manage task workflow statuses from Settings
- organize work against project milestones
- break larger tasks into checklist-style subtasks
- filter work more effectively from the Projects and Tasks screens
- give team members a focused `My Work` area for assigned tasks
- give front-end workspace users a portfolio dashboard and project-specific tabbed workspace
- give managers a starting dashboard for counts, overdue work, and upcoming tasks

## Current Features

### Project Management

- create projects
- edit projects
- mark projects with statuses and priorities
- set project owner/manager
- assign members to a project
- filter projects by search, status, priority, and owner
- store descriptions, notes, dates, and project type/category
- manage project milestones
- edit existing project milestones from the project screen
- view related tasks and project progress summary
- restrict project visibility based on permissions and project membership

### Task Management

- create tasks under projects
- assign each task to one user
- restrict task assignment to the selected project team plus the project owner
- manage task status, priority, dates, effort fields, and milestone linkage
- use configurable task statuses seeded with `To Do`, `In Progress`, `In Review`, and `Done`
- add and complete checklist-style subtasks under each task
- add task comments and mention teammates with `@username`
- move tasks across workflow columns from the front-end Kanban board
- filter tasks by search, project, milestone, status, assignee, priority, and due state
- identify overdue and upcoming tasks using closed workflow statuses
- allow team members to update the status of tasks assigned to them
- provide validation feedback when save rules are not met
- show in-app mention notifications in `My Work`

### Admin and Team Views

- Dashboard
- Projects
- Tasks
- My Work
- Front-end workspace via `[teamops_hub_workspace]`
- Reports placeholder
- Future Modules placeholder
- Settings

## User Roles

### Administrators

- receive TeamOps manager-level capabilities
- can manage projects, tasks, reports, and settings

### TeamOps Managers

- can create and edit projects
- can create and edit tasks
- can review dashboard and reporting areas
- can manage plugin settings

### TeamOps Members

- can access TeamOps
- can view assigned work
- can update the status of their own assigned tasks

## Installation

1. Copy the `teamops-hub` plugin folder into your WordPress `wp-content/plugins/` directory.
2. In the WordPress admin, go to `Plugins`.
3. Activate `TeamOps Hub`.
4. On activation, the plugin creates its custom database tables and registers TeamOps roles/capabilities.

## Recommended First-Time Setup

After activation:

1. Confirm the plugin appears in the WordPress admin menu as `TeamOps Hub`.
2. Review the `Settings` page and confirm the task workflow statuses match your process.
3. Decide which users should be managers and which should be team members.
4. Create your first project.
5. Assign project members.
6. Add milestones under the project if needed.
7. Add tasks under the project.
8. Ask team members to use `My Work` to review assignments and update task status.
9. Create a WordPress page with the `[teamops_hub_workspace]` shortcode if you want a front-end workspace for daily execution.

## Typical Workflow

### For Managers

1. Create a project
2. Set status, priority, owner, and dates
3. Assign project members
4. Create milestones where useful
5. Create tasks inside the project
6. Assign tasks to project team members
7. Track progress through the Dashboard, Projects, and Tasks screens

### For Team Members

1. Open `TeamOps Hub > My Work`
2. Review assigned projects
3. Review assigned tasks
4. Update task statuses as work moves forward

### For Front-End Workspace Users

1. Create or open a WordPress page containing `[teamops_hub_workspace]`
2. Sign in with a TeamOps-enabled account
3. Review the all-project portfolio dashboard
4. Open a project to switch into its dedicated workspace tabs
5. Use `Overview`, `Kanban`, `Tasks`, and `Milestones` to work inside the selected project
6. Move work on the Kanban board or update status from detailed task cards
7. Edit task details, complete checklist items, and post comments from the front end

## Current Technical Approach

TeamOps Hub is built as a custom-table-first plugin. That means core operational data is stored in dedicated plugin tables instead of relying on WordPress posts and post meta for projects and tasks.

This approach is intended to make the plugin easier to scale and easier to extend over time as new business modules are added.

## Custom Tables Used

- `teamops_hub_projects`
- `teamops_hub_project_members`
- `teamops_hub_tasks`
- `teamops_hub_activity_log`
- `teamops_hub_task_statuses`
- `teamops_hub_milestones`
- `teamops_hub_task_comments`
- `teamops_hub_task_comment_mentions`
- `teamops_hub_notifications`

## Current Limitations

This is still an early product foundation, so some areas are intentionally lightweight right now:

- reports are basic placeholders
- list screens are custom admin tables, not full `WP_List_Table` implementations yet
- attachments, dependencies, and time tracking are not yet implemented
- notifications currently focus on in-app mention alerts and are not yet a full delivery system
- runtime behavior still needs broader real-world validation in a live WordPress environment

## Planned Direction

Future expansion is expected to include:

- comments with @mentions
- richer notifications
- attachments
- reporting dashboards and KPI widgets
- activity feed and audit visibility
- REST/API integrations and external connectors

## Documentation

For contributors, maintainers, or technical review:

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

## License

This project is released under the MIT License. See `LICENSE`.

## Notes

- The plugin is multilingual ready through the `teamops-hub` text domain.
- The current member experience is admin-first.
- The plugin now also supports a hybrid front-end workspace through the `[teamops_hub_workspace]` shortcode.
- The architecture is intentionally modular so future modules can plug into the same foundation.
