# Migration and Versioning Notes

- Plugin code version is stored in the main plugin header.
- Database version is stored separately in `teamops_hub_db_version`.
- Role/capability version is stored in `teamops_hub_roles_version`.
- Activation runs schema migration plus role registration.
- Runtime boot checks compare current code constants against stored versions and run upgrades when needed.
- Database version `1.1.0` introduces configurable task statuses and milestones.
- The `1.1.0` migration also normalizes legacy task rows by converting `review` to `in_review` and preserving any other existing task status keys as database-backed workflow statuses.
- Database version `1.2.0` introduces checklist-style task subtasks.
- Database version `1.3.0` introduces task comments, comment mentions, and persisted in-app notifications.
- Uninstall removes version/settings options only; operational data retention can be expanded later with a user-controlled cleanup option.
