<?php
/**
 * Project repository.
 *
 * @package TeamOpsHub\Modules\Projects\Repositories
 */

namespace TeamOpsHub\Modules\Projects\Repositories;

use TeamOpsHub\Helpers\DateHelper;
use TeamOpsHub\Repositories\BaseRepository;

defined( 'ABSPATH' ) || exit;

class ProjectRepository extends BaseRepository {
	/**
	 * Returns all projects for the current context.
	 *
	 * @param bool $only_current_user Limit to memberships for current user.
	 * @return array
	 */
	public function all( $only_current_user = false, array $filters = array() ) {
		global $wpdb;

		$projects_table = $this->table( 'projects' );
		$members_table  = $this->table( 'project_members' );
		$where          = array( '1=1' );
		$values         = array();

		if ( ! empty( $filters['search'] ) ) {
			$where[]  = 'p.title LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
		}

		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'p.status = %s';
			$values[] = sanitize_key( $filters['status'] );
		}

		if ( ! empty( $filters['priority'] ) ) {
			$where[]  = 'p.priority = %s';
			$values[] = sanitize_key( $filters['priority'] );
		}

		if ( ! empty( $filters['owner_user_id'] ) ) {
			$where[]  = 'p.owner_user_id = %d';
			$values[] = absint( $filters['owner_user_id'] );
		}

		if ( $only_current_user ) {
			$user_id = get_current_user_id();
			$where[] = '(pm.user_id = %d OR p.owner_user_id = %d)';
			$values[] = $user_id;
			$values[] = $user_id;

			$sql = "SELECT DISTINCT p.*
				FROM {$projects_table} p
				LEFT JOIN {$members_table} pm ON pm.project_id = p.id
				WHERE " . implode( ' AND ', $where ) . '
				ORDER BY p.archived_at ASC, p.due_date ASC, p.updated_at DESC';

			return $wpdb->get_results(
				$wpdb->prepare( $sql, $values ),
				ARRAY_A
			);
		}

		$sql = "SELECT p.* FROM {$projects_table} p WHERE " . implode( ' AND ', $where ) . ' ORDER BY p.archived_at ASC, p.due_date ASC, p.updated_at DESC';

		if ( empty( $values ) ) {
			return $wpdb->get_results( $sql, ARRAY_A );
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
	}

	/**
	 * Finds one project by id.
	 *
	 * @param int $project_id Project id.
	 * @return array|null
	 */
	public function find( $project_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'projects' )} WHERE id = %d",
				$project_id
			),
			ARRAY_A
		);
	}

	/**
	 * Creates a project.
	 *
	 * @param array $data Sanitized data.
	 * @return int
	 */
	public function create( array $data ) {
		global $wpdb;

		$now     = DateHelper::now();
		$user_id = get_current_user_id();

		$wpdb->insert(
			$this->table( 'projects' ),
			array(
				'title'         => $data['title'],
				'code'          => $data['code'],
				'description'   => $data['description'],
				'status'        => $data['status'],
				'priority'      => $data['priority'],
				'owner_user_id' => $data['owner_user_id'],
				'project_type'  => $data['project_type'],
				'start_date'    => $data['start_date'] ?: null,
				'due_date'      => $data['due_date'] ?: null,
				'notes'         => $data['notes'],
				'created_by'    => $user_id,
				'updated_by'    => $user_id,
				'created_at'    => $now,
				'updated_at'    => $now,
				'archived_at'   => null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates a project.
	 *
	 * @param int   $project_id Project id.
	 * @param array $data Sanitized data.
	 * @return void
	 */
	public function update( $project_id, array $data ) {
		global $wpdb;

		$wpdb->update(
			$this->table( 'projects' ),
			array(
				'title'         => $data['title'],
				'code'          => $data['code'],
				'description'   => $data['description'],
				'status'        => $data['status'],
				'priority'      => $data['priority'],
				'owner_user_id' => $data['owner_user_id'],
				'project_type'  => $data['project_type'],
				'start_date'    => $data['start_date'] ?: null,
				'due_date'      => $data['due_date'] ?: null,
				'notes'         => $data['notes'],
				'updated_by'    => get_current_user_id(),
				'updated_at'    => DateHelper::now(),
				'archived_at'   => 'archived' === $data['status'] ? DateHelper::now() : null,
			),
			array( 'id' => $project_id ),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Replaces project members.
	 *
	 * @param int   $project_id Project id.
	 * @param array $member_ids Member ids.
	 * @return void
	 */
	public function sync_members( $project_id, array $member_ids ) {
		global $wpdb;

		$table = $this->table( 'project_members' );
		$wpdb->delete( $table, array( 'project_id' => $project_id ), array( '%d' ) );

		foreach ( $member_ids as $member_id ) {
			$wpdb->insert(
				$table,
				array(
					'project_id' => $project_id,
					'user_id'    => $member_id,
					'role'       => 'member',
					'joined_at'  => DateHelper::now(),
					'created_by' => get_current_user_id(),
				),
				array( '%d', '%d', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Returns project member ids.
	 *
	 * @param int $project_id Project id.
	 * @return int[]
	 */
	public function member_ids( $project_id ) {
		global $wpdb;

		return array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM {$this->table( 'project_members' )} WHERE project_id = %d",
					$project_id
				)
			)
		);
	}

	/**
	 * Checks whether a user belongs to the project membership list.
	 *
	 * @param int $project_id Project id.
	 * @param int $user_id User id.
	 * @return bool
	 */
	public function is_member( $project_id, $user_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM {$this->table( 'project_members' )} WHERE project_id = %d AND user_id = %d",
				$project_id,
				$user_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Returns summary counts grouped by status.
	 *
	 * @return array
	 */
	public function status_counts() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT status, COUNT(*) AS total FROM {$this->table( 'projects' )} GROUP BY status",
			ARRAY_A
		);
	}
}
