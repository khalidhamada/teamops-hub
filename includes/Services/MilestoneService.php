<?php
/**
 * Milestone business logic.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Modules\Projects\Repositories\ProjectRepository;
use TeamOpsHub\Modules\Tasks\Repositories\MilestoneRepository;

defined( 'ABSPATH' ) || exit;

class MilestoneService {
	/**
	 * Repository.
	 *
	 * @var MilestoneRepository
	 */
	private $milestones;

	/**
	 * Validation service.
	 *
	 * @var ValidationService
	 */
	private $validation;

	/**
	 * Permission service.
	 *
	 * @var PermissionService
	 */
	private $permissions;

	/**
	 * Activity log.
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
	 * Constructor.
	 *
	 * @param PermissionService  $permissions Permission service.
	 * @param ValidationService  $validation Validation service.
	 * @param ActivityLogService $activity_log Activity log.
	 */
	public function __construct( PermissionService $permissions, ValidationService $validation, ActivityLogService $activity_log ) {
		$this->milestones   = new MilestoneRepository();
		$this->permissions  = $permissions;
		$this->validation   = $validation;
		$this->activity_log = $activity_log;
		$this->projects     = new ProjectRepository();
	}

	/**
	 * Returns milestones with optional filters.
	 *
	 * @param array $filters Filters.
	 * @return array
	 */
	public function get_milestones( array $filters = array() ) {
		return $this->milestones->all( $filters );
	}

	/**
	 * Returns one milestone.
	 *
	 * @param int $milestone_id Milestone id.
	 * @return array|null
	 */
	public function get_milestone( $milestone_id ) {
		return $this->milestones->find( $milestone_id );
	}

	/**
	 * Saves a milestone.
	 *
	 * @param array    $input Raw input.
	 * @param int|null $milestone_id Optional milestone id.
	 * @return int|\WP_Error
	 */
	public function save_milestone( array $input, $milestone_id = null ) {
		$data            = $this->validation->sanitize_milestone_data( $input );
		$allowed_statuses = array_keys( $this->status_options() );
		$errors          = $this->validation->validate_milestone_data( $data, $allowed_statuses );

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'teamops_invalid_milestone', implode( ' ', $errors ), $errors );
		}

		$project = $this->projects->find( (int) $data['project_id'] );

		if ( ! $project ) {
			return new \WP_Error( 'teamops_invalid_project', __( 'The selected project could not be found.', 'teamops-hub' ) );
		}

		if ( ! $this->permissions->can_manage_projects() && ! $this->can_view_project( $project ) ) {
			return new \WP_Error( 'teamops_forbidden_project', __( 'You are not allowed to manage milestones for this project.', 'teamops-hub' ) );
		}

		if ( $milestone_id ) {
			$existing = $this->milestones->find( $milestone_id );

			if ( ! $existing ) {
				return new \WP_Error( 'teamops_invalid_milestone', __( 'The milestone you tried to update could not be found.', 'teamops-hub' ) );
			}

			if ( (int) $existing['project_id'] !== (int) $data['project_id'] ) {
				return new \WP_Error( 'teamops_invalid_milestone_project', __( 'Milestones cannot be moved to a different project from this screen.', 'teamops-hub' ) );
			}

			$updated = $this->milestones->update( $milestone_id, $data );

			if ( ! $updated ) {
				return new \WP_Error( 'teamops_milestone_update_failed', __( 'The milestone could not be updated.', 'teamops-hub' ) );
			}

			$this->activity_log->log( 'milestone', $milestone_id, 'updated', 'Milestone updated.' );

			return (int) $milestone_id;
		}

		$milestone_id = $this->milestones->create( $data );

		if ( ! $milestone_id ) {
			return new \WP_Error( 'teamops_milestone_create_failed', __( 'The milestone could not be created.', 'teamops-hub' ) );
		}

		$this->activity_log->log( 'milestone', $milestone_id, 'created', 'Milestone created.' );

		return $milestone_id;
	}

	/**
	 * Returns milestone status options.
	 *
	 * @return array
	 */
	public function status_options() {
		return array(
			'planned'     => __( 'Planned', 'teamops-hub' ),
			'in_progress' => __( 'In Progress', 'teamops-hub' ),
			'completed'   => __( 'Completed', 'teamops-hub' ),
		);
	}

	/**
	 * Checks whether current user can view the project.
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
}
