# Database Schema Notes

## Tables

### `{$wpdb->prefix}teamops_hub_projects`

- `id`
- `title`
- `code`
- `description`
- `status`
- `priority`
- `owner_user_id`
- `project_type`
- `start_date`
- `due_date`
- `notes`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `archived_at`

Indexes target common filters such as status, priority, owner, due date, and archived state.

### `{$wpdb->prefix}teamops_hub_project_members`

- `id`
- `project_id`
- `user_id`
- `role`
- `joined_at`
- `created_by`

Includes a unique composite key on `project_id + user_id`.

### `{$wpdb->prefix}teamops_hub_tasks`

- `id`
- `project_id`
- `title`
- `description`
- `status`
- `priority`
- `milestone_id`
- `assigned_user_id`
- `created_by`
- `updated_by`
- `start_date`
- `due_date`
- `estimated_hours`
- `actual_hours`
- `sort_order`
- `created_at`
- `updated_at`
- `completed_at`

Indexes support project views, assignee views, status counts, milestone filtering, due-date logic, and dashboard summaries.

### `{$wpdb->prefix}teamops_hub_task_statuses`

- `id`
- `status_key`
- `label`
- `color`
- `sort_order`
- `is_default`
- `is_closed`
- `is_active`
- `created_at`
- `updated_at`

This table stores configurable workflow states for tasks. Default statuses are seeded during migration, and legacy task status values are preserved by backfilling unknown keys.

### `{$wpdb->prefix}teamops_hub_milestones`

- `id`
- `project_id`
- `title`
- `description`
- `status`
- `due_date`
- `sort_order`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `completed_at`

Milestones are project-scoped planning entities that tasks can optionally reference.

### `{$wpdb->prefix}teamops_hub_task_subtasks`

- `id`
- `task_id`
- `title`
- `is_completed`
- `sort_order`
- `created_by`
- `updated_by`
- `created_at`
- `updated_at`
- `completed_at`

This table supports checklist-style child work under tasks and provides the first phase of subtask execution without introducing deeper hierarchy yet.

### `{$wpdb->prefix}teamops_hub_task_comments`

- `id`
- `task_id`
- `user_id`
- `content`
- `parent_comment_id`
- `created_at`
- `updated_at`

This table stores task discussion entries and keeps comments tied directly to operational task records instead of WordPress posts or comments.

### `{$wpdb->prefix}teamops_hub_task_comment_mentions`

- `id`
- `comment_id`
- `mentioned_user_id`
- `mention_token`
- `created_at`

This table stores resolved mention targets from task comments so future notification and audit workflows do not need to re-parse comment text.

### `{$wpdb->prefix}teamops_hub_notifications`

- `id`
- `user_id`
- `type`
- `title`
- `body`
- `entity_type`
- `entity_id`
- `is_read`
- `read_at`
- `context`
- `created_at`

This table stores lightweight in-app notifications. The first production-oriented use case is task-comment mentions.

### `{$wpdb->prefix}teamops_hub_activity_log`

Stores lightweight audit events and creates a forward path for richer activity feeds and compliance reporting.

## Migration Strategy

- Database version is stored in `teamops_hub_db_version`.
- `dbDelta()` is used for WordPress-safe schema creation and upgrades.
- Version `1.1.0` adds:
- `milestone_id` to tasks
- `task_statuses` table
- `milestones` table
- status seeding and legacy status normalization
- Version `1.2.0` adds:
  - `task_subtasks` table
  - checklist persistence and completion timestamps
- Version `1.3.0` adds:
  - `task_comments` table
  - `task_comment_mentions` table
  - `notifications` table
  - in-app mention notification persistence
- Future schema revisions should extend `SchemaManager::migrate()` with version-aware upgrade branches when data transformations become more complex.
