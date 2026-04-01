<?php
/**
 * Project business logic.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Modules\Projects\Repositories\ProjectRepository;
use TeamOpsHub\Modules\Tasks\Repositories\TaskRepository;

defined( 'ABSPATH' ) || exit;

class ProjectService {
	/**
	 * Permission service.
	 *
	 * @var PermissionService
	 */
	private $permissions;

	/**
	 * Validation service.
	 *
	 * @var ValidationService
	 */
	private $validation;

	/**
	 * Activity log service.
	 *
	 * @var ActivityLogService
	 */
	private $activity_log;

	/**
	 * Project repository.
	 *
	 * @var ProjectRepository
	 */
	private $projects;

	/**
	 * Task repository.
	 *
	 * @var TaskRepository
	 */
	private $tasks;

	/**
	 * Constructor.
	 *
	 * @param PermissionService $permissions Permission service.
	 * @param ValidationService $validation Validation service.
	 * @param ActivityLogService $activity_log Activity logging service.
	 */
	public function __construct( PermissionService $permissions, ValidationService $validation, ActivityLogService $activity_log ) {
		$this->permissions  = $permissions;
		$this->validation   = $validation;
		$this->activity_log = $activity_log;
		$this->projects     = new ProjectRepository();
		$this->tasks        = new TaskRepository();
	}

	/**
	 * Returns all visible projects.
	 *
	 * @return array
	 */
	public function get_projects( array $filters = array() ) {
		return $this->permissions->can_manage_projects() ? $this->projects->all( false, $filters ) : $this->projects->all( true, $filters );
	}

	/**
	 * Returns project details.
	 *
	 * @param int $project_id Project id.
	 * @return array|null
	 */
	public function get_project( $project_id ) {
		$project = $this->projects->find( $project_id );

		if ( ! $project ) {
			return null;
		}

		$project['member_ids'] = $this->projects->member_ids( $project_id );

		if ( ! $this->permissions->can_manage_projects() && ! $this->can_view_project( $project ) ) {
			return null;
		}

		$project['tasks']      = $this->tasks->all(
			array(
				'project_id' => $project_id,
			)
		);
		$project['progress']   = $this->project_progress( $project['tasks'] );

		return $project;
	}

	/**
	 * Saves a project.
	 *
	 * @param array    $input Input data.
	 * @param int|null $project_id Optional project id.
	 * @return int|\WP_Error
	 */
	public function save_project( array $input, $project_id = null ) {
		$data = $this->validation->sanitize_project_data( $input );
		$data['member_ids'] = $this->normalize_project_members( $data );
		$errors = $this->validation->validate_project_data( $data );

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'teamops_invalid_project', implode( ' ', $errors ), $errors );
		}

		if ( $project_id ) {
			$this->projects->update( $project_id, $data );
			$this->projects->sync_members( $project_id, $data['member_ids'] );
			$this->activity_log->log( 'project', $project_id, 'updated', 'Project updated.' );

			return (int) $project_id;
		}

		$project_id = $this->projects->create( $data );
		$this->projects->sync_members( $project_id, $data['member_ids'] );
		$this->activity_log->log( 'project', $project_id, 'created', 'Project created.' );

		return $project_id;
	}

	/**
	 * Returns dashboard counts by status.
	 *
	 * @return array
	 */
	public function status_counts() {
		return $this->count_by_status( $this->get_projects() );
	}

	/**
	 * Returns options for forms.
	 *
	 * @return array
	 */
	public function form_options() {
		$user_query = array(
			'orderby' => 'display_name',
		);

		if ( ! $this->permissions->can_manage_projects() ) {
			$user_query['include'] = array( get_current_user_id() );
		}

		return array(
			'statuses'   => $this->validation->project_statuses(),
			'priorities' => $this->validation->priorities(),
			'users'      => get_users( $user_query ),
		);
	}

	/**
	 * Calculates project progress.
	 *
	 * @param array $tasks Project tasks.
	 * @return int
	 */
	private function project_progress( array $tasks ) {
		if ( empty( $tasks ) ) {
			return 0;
		}

		$total = count( $tasks );
		$done  = count(
			array_filter(
				$tasks,
				static function ( $task ) {
					return ! empty( $task['completed_at'] );
				}
			)
		);

		return (int) round( ( $done / $total ) * 100 );
	}

	/**
	 * Checks whether current user can view a project.
	 *
	 * @param array $project Project row.
	 * @return bool
	 */
	private function can_view_project( array $project ) {
		$current_user_id = get_current_user_id();

		if ( $current_user_id === (int) $project['owner_user_id'] ) {
			return true;
		}

		return $this->projects->is_member( (int) $project['id'], $current_user_id );
	}

	/**
	 * Ensures project membership includes the owner.
	 *
	 * @param array $data Project data.
	 * @return int[]
	 */
	private function normalize_project_members( array $data ) {
		$member_ids = array_map( 'intval', $data['member_ids'] );

		if ( ! empty( $data['owner_user_id'] ) ) {
			$member_ids[] = (int) $data['owner_user_id'];
		}

		return array_values( array_unique( array_filter( $member_ids ) ) );
	}

	/**
	 * Groups rows by status.
	 *
	 * @param array $items Rows to count.
	 * @return array
	 */
	private function count_by_status( array $items ) {
		$counts = array();

		foreach ( $items as $item ) {
			$status = $item['status'] ?? 'unknown';

			if ( ! isset( $counts[ $status ] ) ) {
				$counts[ $status ] = 0;
			}

			++$counts[ $status ];
		}

		$rows = array();

		foreach ( $counts as $status => $total ) {
			$rows[] = array(
				'status' => $status,
				'total'  => $total,
			);
		}

		return $rows;
	}
}
