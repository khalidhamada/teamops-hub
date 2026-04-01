You are an expert senior WordPress plugin architect and product engineer. I want you to build a production-ready WordPress plugin for internal team coordination and collaboration for an IT company. The plugin must be designed for long-term scalability and future expansion. Use CUSTOM DATABASE TABLES as the core data layer for operational entities. Do NOT use custom post types for projects or tasks except where absolutely justified for a secondary integration purpose. Build the plugin with a modular architecture so future modules can be added cleanly.

PROJECT CONTEXT
The plugin is for managing an internal IT company team with users such as developers, marketers, project managers, designers, admins, and management. The first release will focus on:
1. Project management
2. Integrated task management inside projects

Future modules may include:
- Helpdesk / ticketing
- Document repository / knowledge base
- Team collaboration features
- Notifications
- Reporting / dashboards
- Activity logs / audit trails

OVERALL GOAL
Create a clean, extensible, maintainable WordPress plugin that starts with project and task management but is architected like a platform. I want strong foundations now so future modules can plug into the same user model, permission model, database strategy, UI system, and navigation.

IMPORTANT INSTRUCTIONS
- Use custom database tables for the main business entities.
- Follow WordPress coding standards and native WordPress architectural patterns where appropriate.
- Keep the first version practical and not overengineered, but make the structure future-proof.
- Prioritize maintainability, security, scalability, and clarity.
- Build the plugin in a modular way with separation of concerns.
- Use WordPress roles/capabilities and secure permission checks throughout.
- The plugin should work well in a modern WordPress admin environment.
- Prefer admin-first UX for managers/admins and front-end or dashboard views for team members where needed.
-  Do not cut corners in architecture just to make the MVP faster.
- I want a solid implementation, not a throwaway prototype.
- The plugin should be multilingual ready

WHAT I WANT YOU TO PRODUCE
Build the plugin step by step and keep the implementation organized. Before writing code, internally reason about the architecture and then implement accordingly. As you build, create all necessary files and structure in a professional way.

PLUGIN NAME
TeamOps Hub

MVP MODULES
MODULE 1: PROJECT MANAGEMENT
The system must allow:
- Admin/manager to create projects
- Admin/manager to edit projects
- Admin/manager to archive or close projects
- Assign one or more team members to a project
- Store project title, description, status, start date, due date, priority, owner/manager, and optional client/internal category
- View project progress summary
- Show related tasks under the project
- Restrict project visibility based on permissions and project membership

MODULE 2: TASK MANAGEMENT
The system must allow:
- Admin/manager to create tasks under a project
- Assign a task to one user initially; architect so multi-assignee can be added later
- Store task title, description, status, priority, due date, estimated effort optional, created by, assigned to, and timestamps
- Team members can update their task status
- Managers/admins can edit all tasks
- Show task lists by project, by assignee, by status, by due date
- Include overdue and due soon logic
- Design the task system so comments, attachments, subtasks, dependencies, and time tracking can be added later

REQUIRED ARCHITECTURE
Design the plugin around these layers:

1. BOOTSTRAP LAYER
- Main plugin bootstrap file
- Constants
- Versioning
- Activation/deactivation/uninstall handlers
- Dependency loading
- Module registration

2. DATABASE LAYER
Use custom tables for the operational entities.
Design the schema carefully for MVP plus future growth.

At minimum, plan custom tables for:
- projects
- project_members
- tasks
- optional activity_log table or prepare for it
- optional comments/notes table or prepare for it
- plugin settings if needed via options API unless a table is justified

Design the schema with:
- proper primary keys
- foreign key style relationships where appropriate
- indexes for common filtering
- created_at / updated_at
- created_by / updated_by where useful
- soft-delete or archive strategy where useful
- clean upgrade/migration path for future schema changes

Think through common queries:
- projects assigned to current user
- tasks assigned to current user
- tasks by project and status
- overdue tasks
- dashboard counts
- future ticket/document relationships

3. DOMAIN / BUSINESS LOGIC LAYER
Create structured classes/services for:
- project service
- task service
- permission service
- activity logging service
- notification service stub for future use
- validation / sanitization helpers
- database repository classes or equivalent data-access layer

Avoid mixing SQL, HTML, and permissions logic in one place.

4. ADMIN UI LAYER
Build a professional WordPress admin experience with:
- top-level menu for the plugin
- submenus for Projects, Tasks, Reports placeholder, Settings, future modules placeholder
- list screens for projects and tasks
- create/edit forms
- filters and search where practical
- bulk actions if practical
- good UX for assignment and status updates
- clean notices and validation messages

5. USER / TEAM MEMBER EXPERIENCE
Provide a team member view strategy.
This can be:
- admin-based restricted views
- shortcode-based front-end dashboards
- or a hybrid
Choose the best approach for maintainability and internal usability.

At minimum the member should be able to:
- see assigned projects
- see assigned tasks
- update task status if authorized

6. PERMISSIONS / SECURITY LAYER
Design a permission model with roles/capabilities such as:
- administrator
- plugin manager / project manager
- team member
- optional read-only executive viewer in future

Implement capability-based access control.
Support project-level visibility rules where possible.
Ensure:
- nonce checks
- capability checks
- secure database access
- sanitization and escaping
- no insecure direct object access
- safe AJAX/REST handling if used

7. EXTENSIBILITY LAYER
Architect the plugin so future modules can plug in:
- Helpdesk module
- Document repository module
- Notifications
- Activity feed
- Reports/dashboard widgets
- REST API integrations
- External connectors

Use a structure that allows adding modules without rewriting the core.

FUNCTIONAL REQUIREMENTS
All the fields below are suggestions, feel free to add or modify as you see fit.
PROJECT FIELDS
Include a thoughtful set of project fields such as:
- id
- title
- slug/code if useful
- description
- status
- priority
- owner/manager user id
- start date
- due date
- category/type
- created by
- created at
- updated at
- archived at nullable
- notes optional

TASK FIELDS
Include a thoughtful set of task fields such as:
- id
- project id
- title
- description
- status
- priority
- assigned user id
- created by
- start date optional
- due date
- estimated hours optional
- actual hours placeholder optional
- sort order if needed
- created at
- updated at
- completed at nullable

PROJECT STATUSES
Recommend a practical initial status set, for example:
- planned
- active
- on_hold
- completed
- archived

TASK STATUSES
Recommend a practical initial status set, for example:
- todo
- in_progress
- blocked
- review
- done
Keep the implementation flexible enough for future customization.

PRIORITIES
Support priority values for both projects and tasks:
- low
- medium
- high
- critical

DASHBOARD / REPORTING
For MVP, include at least lightweight reporting or dashboard summaries such as:
- projects count by status
- tasks count by status
- my tasks
- overdue tasks
- upcoming due tasks
- project progress summary

Do not overbuild BI reporting yet, but create the plugin structure so richer analytics can be added later.

SETTINGS
Create a settings strategy using WordPress-native patterns where appropriate.
At minimum consider:
- general plugin settings
- permission defaults
- status configuration strategy placeholder
- notification defaults placeholder
- future module toggles

FUTURE MODULE READINESS
The architecture must anticipate future entities, likely with their own custom tables:
- tickets
- ticket_comments
- documents
- document_versions
- activity stream
- notifications
- audit log
- attachments mapping

Design with this in mind now so table naming, services, and navigation remain consistent.

FILE / FOLDER STRUCTURE
Create a professional plugin folder structure.
At minimum think in terms of:
- bootstrap
- includes/core
- modules/projects
- modules/tasks
- admin
- database
- services
- repositories
- templates/views
- assets/css
- assets/js
- languages
- uninstall/migrations
- tests placeholder

I want the structure to clearly separate:
- core framework pieces
- module-specific functionality
- admin UI
- reusable services/helpers
- database logic

But use best practices for wordpress plugin structures

IMPLEMENTATION PREFERENCES
- Use a database schema version option and migration strategy from day one.
- Use prepared SQL queries.
- Use dbDelta or an equivalent WordPress-safe schema management approach if appropriate.
- Keep the repository/service pattern clean and not overly academic.
- Use AJAX or REST only where it improves UX meaningfully.
- Keep the first version simple enough to work reliably, but with strong foundations.
- Avoid building everything in one giant class.
- Avoid storing core operational entities in wp_posts/wp_postmeta.
- Avoid fragile architecture that would become painful when helpdesk/documents are added.

UX EXPECTATIONS
The admin should feel structured and practical, not cluttered.
Project managers should quickly be able to:
- create a project
- assign members
- create tasks
- monitor progress
- filter tasks
Team members should quickly be able to:
- view assigned tasks
- update task status
- understand what is due and overdue

DELIVERABLE EXPECTATIONS
As you work, do the following in a disciplined order:
1. Decide and establish the architecture
2. Create the plugin file/folder structure
3. Define the database schema and migration/versioning strategy
4. Implement core services and repositories
5. Implement permissions and roles/capabilities
6. Implement admin screens for projects
7. Implement admin screens for tasks
8. Implement member-facing views or restricted views
9. Implement reporting/dashboard summaries
10. Implement settings and placeholders for future modules
11. Add basic polish, validation, and security hardening

WHEN YOU BUILD
- Create all necessary files, not just a few example files
- Keep naming consistent
- Add clear inline documentation where useful
- Think like this will become a serious internal operations plugin
- Make pragmatic decisions and proceed without asking unnecessary questions
- Where a decision is ambiguous, choose the most maintainable and scalable option and note it in comments or documentation

ALSO CREATE
Along with the implementation, generate concise supporting documentation files such as:
- README with plugin purpose, setup, and usage
- Architecture notes
- Database schema notes
- Future roadmap notes
- Any migration/versioning notes helpful for maintenance

IMPORTANT FINAL DIRECTION
Build this plugin as a CUSTOM-TABLE-FIRST WordPress business operations plugin for project and task management, with a modular core that can later support helpdesk and document repository modules without major refactoring.