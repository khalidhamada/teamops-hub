<?php
/**
 * Subtask repository.
 *
 * @package TeamOpsHub\Modules\Tasks\Repositories
 */

namespace TeamOpsHub\Modules\Tasks\Repositories;

use TeamOpsHub\Helpers\DateHelper;
use TeamOpsHub\Repositories\BaseRepository;

defined( 'ABSPATH' ) || exit;

class SubtaskRepository extends BaseRepository {
	/**
	 * Returns subtasks for a task.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function for_task( $task_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'task_subtasks' )} WHERE task_id = %d ORDER BY sort_order ASC, id ASC",
				$task_id
			),
			ARRAY_A
		);
	}

	/**
	 * Finds one subtask.
	 *
	 * @param int $subtask_id Subtask id.
	 * @return array|null
	 */
	public function find( $subtask_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'task_subtasks' )} WHERE id = %d",
				$subtask_id
			),
			ARRAY_A
		);
	}

	/**
	 * Creates a subtask.
	 *
	 * @param array $data Sanitized data.
	 * @return int
	 */
	public function create( array $data ) {
		global $wpdb;

		$now     = DateHelper::now();
		$user_id = get_current_user_id();

		$wpdb->insert(
			$this->table( 'task_subtasks' ),
			array(
				'task_id'       => $data['task_id'],
				'title'         => $data['title'],
				'is_completed'  => $data['is_completed'],
				'sort_order'    => $data['sort_order'],
				'created_by'    => $user_id,
				'updated_by'    => $user_id,
				'created_at'    => $now,
				'updated_at'    => $now,
				'completed_at'  => ! empty( $data['is_completed'] ) ? $now : null,
			),
			array( '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates a subtask.
	 *
	 * @param int   $subtask_id Subtask id.
	 * @param array $data Sanitized data.
	 * @return void
	 */
	public function update( $subtask_id, array $data ) {
		global $wpdb;

		$wpdb->update(
			$this->table( 'task_subtasks' ),
			array(
				'title'        => $data['title'],
				'is_completed' => $data['is_completed'],
				'sort_order'   => $data['sort_order'],
				'updated_by'   => get_current_user_id(),
				'updated_at'   => DateHelper::now(),
				'completed_at' => ! empty( $data['is_completed'] ) ? DateHelper::now() : null,
			),
			array( 'id' => $subtask_id ),
			array( '%s', '%d', '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}
}
