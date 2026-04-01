<?php
/**
 * Milestone repository.
 *
 * @package TeamOpsHub\Modules\Tasks\Repositories
 */

namespace TeamOpsHub\Modules\Tasks\Repositories;

use TeamOpsHub\Helpers\DateHelper;
use TeamOpsHub\Repositories\BaseRepository;

defined( 'ABSPATH' ) || exit;

class MilestoneRepository extends BaseRepository {
	/**
	 * Returns milestones.
	 *
	 * @param array $filters Optional filters.
	 * @return array
	 */
	public function all( array $filters = array() ) {
		global $wpdb;

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $filters['project_id'] ) ) {
			$where[]  = 'project_id = %d';
			$values[] = absint( $filters['project_id'] );
		}

		$sql = "SELECT * FROM {$this->table( 'milestones' )} WHERE " . implode( ' AND ', $where ) . ' ORDER BY due_date ASC, sort_order ASC, id ASC';

		if ( empty( $values ) ) {
			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
	}

	/**
	 * Finds one milestone.
	 *
	 * @param int $milestone_id Milestone id.
	 * @return array|null
	 */
	public function find( $milestone_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'milestones' )} WHERE id = %d",
				$milestone_id
			),
			ARRAY_A
		);
	}

	/**
	 * Creates a milestone.
	 *
	 * @param array $data Sanitized data.
	 * @return int|false
	 */
	public function create( array $data ) {
		global $wpdb;

		$now     = DateHelper::now();
		$user_id = get_current_user_id();

		$inserted = $wpdb->insert(
			$this->table( 'milestones' ),
			array(
				'project_id'   => $data['project_id'],
				'title'        => $data['title'],
				'description'  => $data['description'],
				'status'       => $data['status'],
				'due_date'     => $data['due_date'] ?: null,
				'sort_order'   => $data['sort_order'],
				'created_by'   => $user_id,
				'updated_by'   => $user_id,
				'created_at'   => $now,
				'updated_at'   => $now,
				'completed_at' => 'completed' === $data['status'] ? $now : null,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates a milestone.
	 *
	 * @param int   $milestone_id Milestone id.
	 * @param array $data Sanitized data.
	 * @return bool
	 */
	public function update( $milestone_id, array $data ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table( 'milestones' ),
			array(
				'title'        => $data['title'],
				'description'  => $data['description'],
				'status'       => $data['status'],
				'due_date'     => $data['due_date'] ?: null,
				'sort_order'   => $data['sort_order'],
				'updated_by'   => get_current_user_id(),
				'updated_at'   => DateHelper::now(),
				'completed_at' => 'completed' === $data['status'] ? DateHelper::now() : null,
			),
			array( 'id' => $milestone_id ),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}
}
