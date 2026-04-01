# PLANS.md

## Active Priorities

1. Validate the plugin inside a live WordPress environment
2. Continue Milestone 4 hardening and Phase 1 workflow enrichment
3. Improve list screens, filtering, and UX
4. Prepare the data model and code seams for next-level task/project features
5. Keep the docs synchronized with implementation

## Current Plan Board

### In Progress

- Validate the upgraded project/task workflows in a live WordPress environment
- Validate Batch 1 workflow foundations for configurable task statuses and milestones
- Re-test milestone add and edit flows on the live WordPress site after milestone hardening
- Validate Batch 3 workflow foundations for task comments, mentions, and notifications
- Validate the new front-end workspace shortcode and front-end form actions in a live site
- Validate the new global front-end workspace filter, checkbox checklist UI, and task detail edit form in a live site
- Validate the new front-end Kanban board, including drag-and-drop status moves and permission handling
- Validate the new portfolio dashboard and selected-project tab navigation in the front-end workspace

### Next Up

- Activate the plugin in WordPress and verify the `1.1.0` schema migration creates `task_statuses` and `milestones`
- Activate the plugin in WordPress and verify the `1.2.0` schema migration creates `task_subtasks`
- Test task status management from Settings and confirm default/legacy task statuses resolve correctly
- Test milestone creation from Projects and milestone assignment/filtering in Tasks
- Test milestone editing, invalid milestone submissions, and notice behavior from Projects
- Test checklist item creation, completion toggles, and checklist progress rollups in Tasks and My Work
- Test task comment creation from Tasks and My Work, including valid and invalid `@mentions`
- Test mention notifications, unread counts, and mark-all-read behavior in `My Work`
- Create a live page with `[teamops_hub_workspace]` and test task updates, checklist toggles, comments, and mentions from the front end
- Verify front-end redirects preserve the active workspace filter after status, checklist, comment, and task-detail edits
- Test front-end Kanban moves across all configured statuses and confirm the detailed cards stay in sync after reload
- Test project selection, tab switching, and project-scoped task/milestone views on the new front-end workspace
- Confirm overdue/upcoming behavior works with closed statuses beyond the old `done` key
- Fix any runtime issues that appear in real admin usage

### After Validation

- Decide whether the next Kanban step should land in the admin task screen as well or remain front-end-first for now
- Expand notification triggers beyond mentions to assignment, status change, due soon, and overdue

### Future Work

- Attachment mappings
- Time tracking
- Notification delivery strategies
- REST API integration layer
- Helpdesk and document modules

## Open Risks

- Syntax is validated, but runtime WordPress integration is not yet verified
- Schema migration and default-status seeding need live WordPress verification
- Schema migration and checklist persistence need live WordPress verification
- Some workflow assumptions may need adjustment once real users interact with the admin screens
- Milestone workflows were recently corrected and still need live confirmation after the fix
- Comment mentions currently resolve against project participants using `@user_login`, which may need UX refinement after live testing
- Front-end workspace filtering and redirects should be checked on the final permalink setup to confirm shortcode-page behavior feels stable
- Front-end task editing now exposes more fields, so member-vs-manager permissions should be validated carefully in live usage
- Front-end Kanban drag-and-drop should be tested across themes and mobile layouts to confirm it remains usable outside wp-admin
- The new portfolio-to-project tab structure should be checked for clarity on small screens and with longer project names
- More workflow UI will likely push the plugin toward reusable partials or `WP_List_Table` implementations soon

## Decision Notes

- Member execution remains admin-based for now, but the hybrid direction stays intact
- Workflow statuses are now database-backed and should be treated as configurable platform data
- Milestones are project-scoped planning entities and the first step toward richer Phase 1 workflow management

## Update Rule

When meaningful work is completed:

- Move finished items out of active priorities where appropriate
- Add the next concrete implementation steps
- Capture any new risks or decisions here
