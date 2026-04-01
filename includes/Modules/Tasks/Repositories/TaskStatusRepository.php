<?php
/**
 * Task status repository.
 *
 * @package TeamOpsHub\Modules\Tasks\Repositories
 */

namespace TeamOpsHub\Modules\Tasks\Repositories;

use TeamOpsHub\Helpers\DateHelper;
use TeamOpsHub\Repositories\BaseRepository;

defined( 'ABSPATH' ) || exit;

class TaskStatusRepository extends BaseRepository {
	/**
	 * Returns active task statuses ordered for workflow usage.
	 *
	 * @return array
	 */
	public function all_active() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$this->table( 'task_statuses' )} WHERE is_active = 1 ORDER BY sort_order ASC, id ASC",
			ARRAY_A
		);
	}

	/**
	 * Finds one status by key.
	 *
	 * @param string $status_key Status key.
	 * @return array|null
	 */
	public function find_by_key( $status_key ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'task_statuses' )} WHERE status_key = %s",
				sanitize_key( $status_key )
			),
			ARRAY_A
		);
	}

	/**
	 * Creates a task status.
	 *
	 * @param array $data Sanitized data.
	 * @return int
	 */
	public function create( array $data ) {
		global $wpdb;

		$wpdb->insert(
			$this->table( 'task_statuses' ),
			array(
				'status_key' => $data['status_key'],
				'label'      => $data['label'],
				'color'      => $data['color'],
				'sort_order' => $data['sort_order'],
				'is_default' => $data['is_default'],
				'is_closed'  => $data['is_closed'],
				'is_active'  => $data['is_active'],
				'created_at' => DateHelper::now(),
				'updated_at' => DateHelper::now(),
			),
			array( '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates a task status.
	 *
	 * @param int   $status_id Status id.
	 * @param array $data Sanitized data.
	 * @return void
	 */
	public function update( $status_id, array $data ) {
		global $wpdb;

		$wpdb->update(
			$this->table( 'task_statuses' ),
			array(
				'label'      => $data['label'],
				'color'      => $data['color'],
				'sort_order' => $data['sort_order'],
				'is_closed'  => $data['is_closed'],
				'is_active'  => $data['is_active'],
				'updated_at' => DateHelper::now(),
			),
			array( 'id' => $status_id ),
			array( '%s', '%s', '%d', '%d', '%d', '%s' ),
			array( '%d' )
		);
	}
}
