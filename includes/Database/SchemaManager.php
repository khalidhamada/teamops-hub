<?php
/**
 * Database schema management.
 *
 * @package TeamOpsHub\Database
 */

namespace TeamOpsHub\Database;

defined( 'ABSPATH' ) || exit;

class SchemaManager {
	/**
	 * Creates or updates plugin tables.
	 *
	 * @return void
	 */
	public function migrate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$projects_table   = $this->table( 'projects' );
		$members_table    = $this->table( 'project_members' );
		$tasks_table      = $this->table( 'tasks' );
		$activity_table   = $this->table( 'activity_log' );
		$statuses_table   = $this->table( 'task_statuses' );
		$milestones_table = $this->table( 'milestones' );
		$subtasks_table   = $this->table( 'task_subtasks' );
		$comments_table   = $this->table( 'task_comments' );
		$mentions_table   = $this->table( 'task_comment_mentions' );
		$notifications_table = $this->table( 'notifications' );

		$projects_sql = "CREATE TABLE {$projects_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(190) NOT NULL,
			code VARCHAR(50) DEFAULT '' NOT NULL,
			description LONGTEXT NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'planned',
			priority VARCHAR(20) NOT NULL DEFAULT 'medium',
			owner_user_id BIGINT UNSIGNED NOT NULL,
			project_type VARCHAR(100) DEFAULT '' NOT NULL,
			start_date DATE NULL,
			due_date DATE NULL,
			notes LONGTEXT NULL,
			created_by BIGINT UNSIGNED NOT NULL,
			updated_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			archived_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY priority (priority),
			KEY owner_user_id (owner_user_id),
			KEY due_date (due_date),
			KEY archived_at (archived_at)
		) {$charset_collate};";

		$members_sql = "CREATE TABLE {$members_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			project_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			role VARCHAR(50) NOT NULL DEFAULT 'member',
			joined_at DATETIME NOT NULL,
			created_by BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY project_user (project_id, user_id),
			KEY user_id (user_id),
			KEY role (role)
		) {$charset_collate};";

		$tasks_sql = "CREATE TABLE {$tasks_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			project_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(190) NOT NULL,
			description LONGTEXT NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'todo',
			priority VARCHAR(20) NOT NULL DEFAULT 'medium',
			milestone_id BIGINT UNSIGNED NULL,
			assigned_user_id BIGINT UNSIGNED NOT NULL,
			created_by BIGINT UNSIGNED NOT NULL,
			updated_by BIGINT UNSIGNED NOT NULL,
			start_date DATE NULL,
			due_date DATE NULL,
			estimated_hours DECIMAL(8,2) NULL,
			actual_hours DECIMAL(8,2) NULL,
			sort_order INT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			completed_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY project_id (project_id),
			KEY assigned_user_id (assigned_user_id),
			KEY milestone_id (milestone_id),
			KEY status (status),
			KEY priority (priority),
			KEY due_date (due_date),
			KEY completed_at (completed_at),
			KEY project_status (project_id, status)
		) {$charset_collate};";

		$activity_sql = "CREATE TABLE {$activity_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			entity_type VARCHAR(50) NOT NULL,
			entity_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(50) NOT NULL,
			description TEXT NULL,
			context LONGTEXT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY entity_lookup (entity_type, entity_id),
			KEY action (action),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$statuses_sql = "CREATE TABLE {$statuses_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			status_key VARCHAR(50) NOT NULL,
			label VARCHAR(100) NOT NULL,
			color VARCHAR(20) NOT NULL DEFAULT '#2271b1',
			sort_order INT UNSIGNED NOT NULL DEFAULT 0,
			is_default TINYINT(1) NOT NULL DEFAULT 0,
			is_closed TINYINT(1) NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY status_key (status_key),
			KEY sort_order (sort_order),
			KEY is_active (is_active)
		) {$charset_collate};";

		$milestones_sql = "CREATE TABLE {$milestones_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			project_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(190) NOT NULL,
			description LONGTEXT NULL,
			status VARCHAR(50) NOT NULL DEFAULT 'planned',
			due_date DATE NULL,
			sort_order INT UNSIGNED NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NOT NULL,
			updated_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			completed_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY project_id (project_id),
			KEY status (status),
			KEY due_date (due_date),
			KEY sort_order (sort_order)
		) {$charset_collate};";

		$subtasks_sql = "CREATE TABLE {$subtasks_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			task_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(190) NOT NULL,
			is_completed TINYINT(1) NOT NULL DEFAULT 0,
			sort_order INT UNSIGNED NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NOT NULL,
			updated_by BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			completed_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY task_id (task_id),
			KEY is_completed (is_completed),
			KEY sort_order (sort_order)
		) {$charset_collate};";

		$comments_sql = "CREATE TABLE {$comments_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			task_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			content LONGTEXT NOT NULL,
			parent_comment_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY task_id (task_id),
			KEY user_id (user_id),
			KEY parent_comment_id (parent_comment_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$mentions_sql = "CREATE TABLE {$mentions_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			comment_id BIGINT UNSIGNED NOT NULL,
			mentioned_user_id BIGINT UNSIGNED NOT NULL,
			mention_token VARCHAR(190) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY comment_id (comment_id),
			KEY mentioned_user_id (mentioned_user_id),
			KEY mention_token (mention_token)
		) {$charset_collate};";

		$notifications_sql = "CREATE TABLE {$notifications_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(50) NOT NULL,
			title VARCHAR(190) NOT NULL,
			body TEXT NULL,
			entity_type VARCHAR(50) NOT NULL,
			entity_id BIGINT UNSIGNED NOT NULL,
			is_read TINYINT(1) NOT NULL DEFAULT 0,
			read_at DATETIME NULL,
			context LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY type (type),
			KEY entity_lookup (entity_type, entity_id),
			KEY unread_lookup (user_id, is_read),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $projects_sql );
		dbDelta( $members_sql );
		dbDelta( $tasks_sql );
		dbDelta( $activity_sql );
		dbDelta( $statuses_sql );
		dbDelta( $milestones_sql );
		dbDelta( $subtasks_sql );
		dbDelta( $comments_sql );
		dbDelta( $mentions_sql );
		dbDelta( $notifications_sql );

		$this->seed_task_statuses();

		update_option( 'teamops_hub_db_version', TEAMOPS_HUB_DB_VERSION );
	}

	/**
	 * Seeds default and legacy task statuses.
	 *
	 * @return void
	 */
	private function seed_task_statuses() {
		global $wpdb;

		$table       = $this->table( 'task_statuses' );
		$tasks_table = $this->table( 'tasks' );
		$now         = current_time( 'mysql' );
		$defaults    = array(
			array(
				'status_key' => 'todo',
				'label'      => __( 'To Do', 'teamops-hub' ),
				'color'      => '#6b7280',
				'sort_order' => 10,
				'is_default' => 1,
				'is_closed'  => 0,
			),
			array(
				'status_key' => 'in_progress',
				'label'      => __( 'In Progress', 'teamops-hub' ),
				'color'      => '#2271b1',
				'sort_order' => 20,
				'is_default' => 1,
				'is_closed'  => 0,
			),
			array(
				'status_key' => 'in_review',
				'label'      => __( 'In Review', 'teamops-hub' ),
				'color'      => '#996800',
				'sort_order' => 30,
				'is_default' => 1,
				'is_closed'  => 0,
			),
			array(
				'status_key' => 'done',
				'label'      => __( 'Done', 'teamops-hub' ),
				'color'      => '#008a20',
				'sort_order' => 40,
				'is_default' => 1,
				'is_closed'  => 1,
			),
		);

		foreach ( $defaults as $status ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE status_key = %s",
					$status['status_key']
				)
			);

			if ( $exists ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'status_key' => $status['status_key'],
					'label'      => $status['label'],
					'color'      => $status['color'],
					'sort_order' => $status['sort_order'],
					'is_default' => $status['is_default'],
					'is_closed'  => $status['is_closed'],
					'is_active'  => 1,
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
			);
		}

		$wpdb->update(
			$tasks_table,
			array( 'status' => 'in_review' ),
			array( 'status' => 'review' ),
			array( '%s' ),
			array( '%s' )
		);

		$legacy_statuses = $wpdb->get_col( "SELECT DISTINCT status FROM {$tasks_table} WHERE status <> ''" );

		foreach ( $legacy_statuses as $legacy_status ) {
			$legacy_status = sanitize_key( $legacy_status );

			if ( '' === $legacy_status ) {
				continue;
			}

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE status_key = %s",
					$legacy_status
				)
			);

			if ( $exists ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'status_key' => $legacy_status,
					'label'      => ucwords( str_replace( '_', ' ', $legacy_status ) ),
					'color'      => '#7e8993',
					'sort_order' => 100,
					'is_default' => 0,
					'is_closed'  => 0,
					'is_active'  => 1,
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Returns prefixed table name.
	 *
	 * @param string $suffix Table suffix.
	 * @return string
	 */
	public function table( $suffix ) {
		global $wpdb;

		return $wpdb->prefix . 'teamops_hub_' . $suffix;
	}
}
