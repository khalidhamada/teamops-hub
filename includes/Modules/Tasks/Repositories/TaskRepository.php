<?php
/**
 * Task repository.
 *
 * @package TeamOpsHub\Modules\Tasks\Repositories
 */

namespace TeamOpsHub\Modules\Tasks\Repositories;

use TeamOpsHub\Helpers\DateHelper;
use TeamOpsHub\Repositories\BaseRepository;

defined( 'ABSPATH' ) || exit;

class TaskRepository extends BaseRepository {
	/**
	 * Returns tasks.
	 *
	 * @param array $filters Optional filters.
	 * @return array
	 */
	public function all( array $filters = array() ) {
		global $wpdb;

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $filters['search'] ) ) {
			$where[]  = 'title LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
		}

		if ( ! empty( $filters['project_id'] ) ) {
			$where[]  = 'project_id = %d';
			$values[] = absint( $filters['project_id'] );
		}

		if ( ! empty( $filters['milestone_id'] ) ) {
			$where[]  = 'milestone_id = %d';
			$values[] = absint( $filters['milestone_id'] );
		}

		if ( ! empty( $filters['assigned_user_id'] ) ) {
			$where[]  = 'assigned_user_id = %d';
			$values[] = absint( $filters['assigned_user_id'] );
		}

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['priority'] ) ) {
			$where[]  = 'priority = %s';
			$values[] = sanitize_key( $filters['priority'] );
		}

		if ( ! empty( $filters['due_bucket'] ) ) {
			$today = DateHelper::today();
			$closed_statuses = ! empty( $filters['closed_statuses'] ) ? array_map( 'sanitize_key', (array) $filters['closed_statuses'] ) : array( 'done' );
			$placeholders    = implode( ',', array_fill( 0, count( $closed_statuses ), '%s' ) );

			if ( 'overdue' === $filters['due_bucket'] ) {
				$where[]  = "due_date < %s AND status NOT IN ({$placeholders})";
				$values[] = $today;
				$values   = array_merge( $values, $closed_statuses );
			} elseif ( 'due_soon' === $filters['due_bucket'] ) {
				$where[]  = "due_date BETWEEN %s AND %s AND status NOT IN ({$placeholders})";
				$values[] = $today;
				$values[] = gmdate( 'Y-m-d', strtotime( '+7 days', strtotime( $today ) ) );
				$values   = array_merge( $values, $closed_statuses );
			} elseif ( 'no_due_date' === $filters['due_bucket'] ) {
				$where[] = "(due_date IS NULL OR due_date = '')";
			}
		}

		$sql = "SELECT * FROM {$this->table( 'tasks' )} WHERE " . implode( ' AND ', $where ) . ' ORDER BY due_date ASC, updated_at DESC';

		if ( empty( $values ) ) {
			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
	}

	/**
	 * Finds one task.
	 *
	 * @param int $task_id Task id.
	 * @return array|null
	 */
	public function find( $task_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'tasks' )} WHERE id = %d",
				$task_id
			),
			ARRAY_A
		);
	}

	/**
	 * Creates a task.
	 *
	 * @param array $data Sanitized task data.
	 * @return int
	 */
	public function create( array $data ) {
		global $wpdb;

		$now     = DateHelper::now();
		$user_id = get_current_user_id();

		$wpdb->insert(
			$this->table( 'tasks' ),
			array(
				'project_id'       => $data['project_id'],
				'title'            => $data['title'],
				'description'      => $data['description'],
				'status'           => $data['status'],
				'priority'         => $data['priority'],
				'milestone_id'     => $data['milestone_id'] ?: null,
				'assigned_user_id' => $data['assigned_user_id'],
				'created_by'       => $user_id,
				'updated_by'       => $user_id,
				'start_date'       => $data['start_date'] ?: null,
				'due_date'         => $data['due_date'] ?: null,
				'estimated_hours'  => $data['estimated_hours'],
				'actual_hours'     => $data['actual_hours'],
				'sort_order'       => 0,
				'created_at'       => $now,
				'updated_at'       => $now,
				'completed_at'     => $data['completed_at'] ?? null,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates a task.
	 *
	 * @param int   $task_id Task id.
	 * @param array $data Sanitized task data.
	 * @return void
	 */
	public function update( $task_id, array $data ) {
		global $wpdb;

		$wpdb->update(
			$this->table( 'tasks' ),
			array(
				'project_id'       => $data['project_id'],
				'title'            => $data['title'],
				'description'      => $data['description'],
				'status'           => $data['status'],
				'priority'         => $data['priority'],
				'milestone_id'     => $data['milestone_id'] ?: null,
				'assigned_user_id' => $data['assigned_user_id'],
				'updated_by'       => get_current_user_id(),
				'start_date'       => $data['start_date'] ?: null,
				'due_date'         => $data['due_date'] ?: null,
				'estimated_hours'  => $data['estimated_hours'],
				'actual_hours'     => $data['actual_hours'],
				'updated_at'       => DateHelper::now(),
				'completed_at'     => $data['completed_at'] ?? null,
			),
			array( 'id' => $task_id ),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Returns status counts.
	 *
	 * @return array
	 */
	public function status_counts() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT status, COUNT(*) AS total FROM {$this->table( 'tasks' )} GROUP BY status",
			ARRAY_A
		);
	}

	/**
	 * Returns overdue tasks.
	 *
	 * @param int|null $user_id Optional assignee filter.
	 * @param array    $closed_statuses Closed statuses.
	 * @return array
	 */
	public function overdue( $user_id = null, array $closed_statuses = array( 'done' ) ) {
		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $closed_statuses ), '%s' ) );
		$sql = "SELECT * FROM {$this->table( 'tasks' )} WHERE due_date < %s AND status NOT IN ({$placeholders})";
		$args = array( DateHelper::today() );
		$args = array_merge( $args, $closed_statuses );

		if ( $user_id ) {
			$sql   .= ' AND assigned_user_id = %d';
			$args[] = $user_id;
		}

		$sql .= ' ORDER BY due_date ASC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
	}

	/**
	 * Returns upcoming tasks due within given days.
	 *
	 * @param int      $days Days ahead.
	 * @param int|null $user_id Optional assignee filter.
	 * @param array    $closed_statuses Closed statuses.
	 * @return array
	 */
	public function upcoming( $days = 7, $user_id = null, array $closed_statuses = array( 'done' ) ) {
		global $wpdb;

		$today = DateHelper::today();
		$end   = gmdate( 'Y-m-d', strtotime( "+{$days} days", strtotime( $today ) ) );
		$placeholders = implode( ',', array_fill( 0, count( $closed_statuses ), '%s' ) );
		$sql   = "SELECT * FROM {$this->table( 'tasks' )} WHERE due_date BETWEEN %s AND %s AND status NOT IN ({$placeholders})";
		$args  = array( $today, $end );
		$args  = array_merge( $args, $closed_statuses );

		if ( $user_id ) {
			$sql   .= ' AND assigned_user_id = %d';
			$args[] = $user_id;
		}

		$sql .= ' ORDER BY due_date ASC';

		return $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
	}
}
