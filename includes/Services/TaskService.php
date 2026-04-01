<?php
/**
 * Task business logic.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Modules\Projects\Repositories\ProjectRepository;
use TeamOpsHub\Modules\Tasks\Repositories\TaskRepository;

defined( 'ABSPATH' ) || exit;

class TaskService {
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
	 * Task status service.
	 *
	 * @var TaskStatusService
	 */
	private $task_statuses;

	/**
	 * Milestone service.
	 *
	 * @var MilestoneService
	 */
	private $milestones;

	/**
	 * Subtask service.
	 *
	 * @var SubtaskService
	 */
	private $subtasks;

	/**
	 * Task repository.
	 *
	 * @var TaskRepository
	 */
	private $tasks;

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
	 * @param ActivityLogService $activity_log Activity service.
	 * @param TaskStatusService  $task_statuses Task status service.
	 * @param MilestoneService   $milestones Milestone service.
	 * @param SubtaskService     $subtasks Subtask service.
	 */
	public function __construct( PermissionService $permissions, ValidationService $validation, ActivityLogService $activity_log, TaskStatusService $task_statuses, MilestoneService $milestones, SubtaskService $subtasks ) {
		$this->permissions  = $permissions;
		$this->validation   = $validation;
		$this->activity_log = $activity_log;
		$this->task_statuses = $task_statuses;
		$this->milestones    = $milestones;
		$this->subtasks      = $subtasks;
		$this->tasks        = new TaskRepository();
		$this->projects     = new ProjectRepository();
	}

	/**
	 * Returns tasks visible to current user.
	 *
	 * @param array $filters Optional filters.
	 * @return array
	 */
	public function get_tasks( array $filters = array() ) {
		if ( ! $this->permissions->can_manage_tasks() ) {
			$filters['assigned_user_id'] = get_current_user_id();
		}

		$filters['closed_statuses'] = $this->task_statuses->closed_keys();

		return array_map(
			array( $this, 'enrich_task' ),
			$this->tasks->all( $filters )
		);
	}

	/**
	 * Finds one task.
	 *
	 * @param int $task_id Task id.
	 * @return array|null
	 */
	public function get_task( $task_id ) {
		return $this->load_task( $task_id, true );
	}

	/**
	 * Returns a raw visible task without computed enrichments.
	 *
	 * @param int $task_id Task id.
	 * @return array|null
	 */
	public function get_task_record( $task_id ) {
		return $this->load_task( $task_id, false );
	}

	/**
	 * Saves a task.
	 *
	 * @param array    $input Input data.
	 * @param int|null $task_id Optional task id.
	 * @return int|\WP_Error
	 */
	public function save_task( array $input, $task_id = null ) {
		$data = $this->validation->sanitize_task_data( $input );
		$data['status'] = $this->task_statuses->normalize_status_key( $data['status'] );
		$data['completed_at'] = $this->task_statuses->is_closed( $data['status'] ) ? current_time( 'mysql' ) : null;
		$allowed_assignee_ids = $this->allowed_assignee_ids_for_project( (int) $data['project_id'] );
		$errors = $this->validation->validate_task_data( $data, $allowed_assignee_ids );

		if ( ! empty( $data['milestone_id'] ) && ! $this->milestone_belongs_to_project( (int) $data['milestone_id'], (int) $data['project_id'] ) ) {
			$errors[] = __( 'The selected milestone does not belong to the selected project.', 'teamops-hub' );
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'teamops_invalid_task', implode( ' ', $errors ), $errors );
		}

		if ( $task_id ) {
			$this->tasks->update( $task_id, $data );
			$this->activity_log->log( 'task', $task_id, 'updated', 'Task updated.' );

			return (int) $task_id;
		}

		$task_id = $this->tasks->create( $data );
		$this->activity_log->log( 'task', $task_id, 'created', 'Task created.' );

		return $task_id;
	}

	/**
	 * Updates a task status for current user if permitted.
	 *
	 * @param int    $task_id Task id.
	 * @param string $status New status.
	 * @return bool
	 */
	public function update_status( $task_id, $status ) {
		$task = $this->get_task( $task_id );

		if ( ! $task || ! $this->permissions->can_update_task( $task ) ) {
			return false;
		}

		$task['status'] = $this->task_statuses->normalize_status_key( $status );
		$this->tasks->update( $task_id, $task );
		$this->activity_log->log( 'task', $task_id, 'status_updated', 'Task status updated.' );

		return true;
	}

	/**
	 * Updates task details from the member workspace.
	 *
	 * Members can update execution-oriented fields on tasks they are allowed to work on.
	 * Managers keep access to milestone, assignee, and estimate fields from the same screen.
	 *
	 * @param int   $task_id Task id.
	 * @param array $input Submitted task input.
	 * @return int|\WP_Error
	 */
	public function update_workspace_task( $task_id, array $input ) {
		$task = $this->get_task_record( $task_id );

		if ( ! $task || ! $this->permissions->can_update_task( $task ) ) {
			return new \WP_Error( 'teamops_invalid_task', __( 'The task could not be updated.', 'teamops-hub' ) );
		}

		$data = array(
			'project_id'       => (int) $task['project_id'],
			'title'            => $input['title'] ?? $task['title'],
			'description'      => $input['description'] ?? $task['description'],
			'status'           => $input['status'] ?? $task['status'],
			'priority'         => $input['priority'] ?? $task['priority'],
			'milestone_id'     => $task['milestone_id'] ?? 0,
			'assigned_user_id' => (int) ( $task['assigned_user_id'] ?? 0 ),
			'start_date'       => $input['start_date'] ?? $task['start_date'],
			'due_date'         => $input['due_date'] ?? $task['due_date'],
			'estimated_hours'  => $task['estimated_hours'] ?? '',
			'actual_hours'     => $input['actual_hours'] ?? $task['actual_hours'],
		);

		if ( $this->permissions->can_manage_tasks() ) {
			$data['milestone_id']     = $input['milestone_id'] ?? $data['milestone_id'];
			$data['assigned_user_id'] = $input['assigned_user_id'] ?? $data['assigned_user_id'];
			$data['estimated_hours']  = $input['estimated_hours'] ?? $data['estimated_hours'];
		}

		return $this->save_task( $data, $task_id );
	}

	/**
	 * Returns status counts.
	 *
	 * @return array
	 */
	public function status_counts() {
		return $this->count_by_status( $this->get_tasks() );
	}

	/**
	 * Returns overdue tasks for current user or all managers.
	 *
	 * @return array
	 */
	public function overdue_tasks() {
		$user_id = $this->permissions->can_manage_tasks() ? null : get_current_user_id();

		return $this->tasks->overdue( $user_id, $this->task_statuses->closed_keys() );
	}

	/**
	 * Returns upcoming tasks for current user or all managers.
	 *
	 * @return array
	 */
	public function upcoming_tasks() {
		$user_id = $this->permissions->can_manage_tasks() ? null : get_current_user_id();

		return $this->tasks->upcoming( 7, $user_id, $this->task_statuses->closed_keys() );
	}

	/**
	 * Returns options for forms.
	 *
	 * @return array
	 */
	public function form_options() {
		$projects = $this->permissions->can_manage_tasks() ? $this->projects->all() : $this->projects->all( true );

		foreach ( $projects as &$project ) {
			$project['member_ids'] = $this->allowed_assignee_ids_for_project( (int) $project['id'] );
		}

		unset( $project );

		$user_query = array(
			'orderby' => 'display_name',
		);

		if ( ! $this->permissions->can_manage_tasks() ) {
			$user_query['include'] = array( get_current_user_id() );
		}

		return array(
			'projects'   => $projects,
			'statuses'   => $this->task_statuses->options(),
			'priorities' => $this->validation->priorities(),
			'milestones' => $this->visible_milestones( $projects ),
			'users'      => get_users( $user_query ),
		);
	}

	/**
	 * Returns task status options.
	 *
	 * @return array
	 */
	public function status_options() {
		return $this->task_statuses->options();
	}

	/**
	 * Returns workflow status rows with metadata.
	 *
	 * @return array
	 */
	public function status_definitions() {
		return $this->task_statuses->all();
	}

	/**
	 * Returns one task status label.
	 *
	 * @param string $status_key Status key.
	 * @return string
	 */
	public function status_label( $status_key ) {
		return $this->task_statuses->label( $status_key );
	}

	/**
	 * Returns checklist summary for a task.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function checklist_summary( $task_id ) {
		return $this->subtasks->summary_for_task( $task_id );
	}

	/**
	 * Returns allowed assignee ids for a project.
	 *
	 * @param int $project_id Project id.
	 * @return int[]
	 */
	private function allowed_assignee_ids_for_project( $project_id ) {
		if ( empty( $project_id ) ) {
			return array();
		}

		$project = $this->projects->find( $project_id );

		if ( ! $project ) {
			return array();
		}

		$member_ids = $this->projects->member_ids( $project_id );
		$member_ids[] = (int) $project['owner_user_id'];

		return array_values( array_unique( array_filter( array_map( 'intval', $member_ids ) ) ) );
	}

	/**
	 * Returns milestones visible for the given projects.
	 *
	 * @param array $projects Visible projects.
	 * @return array
	 */
	private function visible_milestones( array $projects ) {
		$project_ids = array_map( 'intval', wp_list_pluck( $projects, 'id' ) );
		$milestones  = $this->milestones->get_milestones();

		return array_values(
			array_filter(
				$milestones,
				static function ( $milestone ) use ( $project_ids ) {
					return in_array( (int) $milestone['project_id'], $project_ids, true );
				}
			)
		);
	}

	/**
	 * Checks whether a milestone belongs to the selected project.
	 *
	 * @param int $milestone_id Milestone id.
	 * @param int $project_id Project id.
	 * @return bool
	 */
	private function milestone_belongs_to_project( $milestone_id, $project_id ) {
		if ( empty( $milestone_id ) ) {
			return true;
		}

		$milestone = $this->milestones->get_milestone( $milestone_id );

		if ( ! $milestone ) {
			return false;
		}

		return (int) $milestone['project_id'] === (int) $project_id;
	}

	/**
	 * Loads a task and optionally enriches it.
	 *
	 * @param int  $task_id Task id.
	 * @param bool $enrich Whether to attach computed metadata.
	 * @return array|null
	 */
	private function load_task( $task_id, $enrich ) {
		$task = $this->tasks->find( $task_id );

		if ( ! $task ) {
			return null;
		}

		if ( ! $this->permissions->can_manage_tasks() && get_current_user_id() !== (int) $task['assigned_user_id'] ) {
			return null;
		}

		return $enrich ? $this->enrich_task( $task ) : $task;
	}

	/**
	 * Adds workflow metadata used across pages.
	 *
	 * @param array $task Task row.
	 * @return array
	 */
	private function enrich_task( array $task ) {
		$task['status_label']      = $this->status_label( $task['status'] ?? '' );
		$task['checklist_summary'] = $this->subtasks->summary_for_task( (int) $task['id'] );

		return $task;
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
