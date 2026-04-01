<?php
/**
 * Subtask business logic.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Modules\Tasks\Repositories\SubtaskRepository;

defined( 'ABSPATH' ) || exit;

class SubtaskService {
	/**
	 * Repository.
	 *
	 * @var SubtaskRepository
	 */
	private $subtasks;

	/**
	 * Validation service.
	 *
	 * @var ValidationService
	 */
	private $validation;

	/**
	 * Task service.
	 *
	 * @var TaskService
	 */
	private $tasks;

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
	 * Constructor.
	 *
	 * @param ValidationService  $validation Validation service.
	 * @param PermissionService  $permissions Permission service.
	 * @param ActivityLogService $activity_log Activity log.
	 */
	public function __construct( ValidationService $validation, PermissionService $permissions, ActivityLogService $activity_log ) {
		$this->subtasks     = new SubtaskRepository();
		$this->validation   = $validation;
		$this->permissions  = $permissions;
		$this->activity_log = $activity_log;
	}

	/**
	 * Sets the task service after construction to avoid bootstrap cycles.
	 *
	 * @param TaskService $tasks Task service.
	 * @return void
	 */
	public function set_task_service( TaskService $tasks ) {
		$this->tasks = $tasks;
	}

	/**
	 * Returns subtasks for a task when visible.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function get_subtasks( $task_id ) {
		$task = $this->tasks ? $this->tasks->get_task_record( $task_id ) : null;

		if ( ! $task ) {
			return array();
		}

		return $this->subtasks->for_task( $task_id );
	}

	/**
	 * Saves a subtask.
	 *
	 * @param array $input Raw input.
	 * @return int|\WP_Error
	 */
	public function save_subtask( array $input ) {
		$data = $this->validation->sanitize_subtask_data( $input );
		$task = $this->tasks ? $this->tasks->get_task_record( (int) $data['task_id'] ) : null;

		if ( ! $task ) {
			return new \WP_Error( 'teamops_invalid_subtask_task', __( 'The selected task is not available.', 'teamops-hub' ) );
		}

		$errors = $this->validation->validate_subtask_data( $data );

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'teamops_invalid_subtask', implode( ' ', $errors ), $errors );
		}

		$subtask_id = $this->subtasks->create( $data );
		$this->activity_log->log( 'subtask', $subtask_id, 'created', 'Subtask created.', array( 'task_id' => $data['task_id'] ) );

		return $subtask_id;
	}

	/**
	 * Toggles subtask completion when the user can update the parent task.
	 *
	 * @param int  $subtask_id Subtask id.
	 * @param bool $is_completed Completion state.
	 * @return bool
	 */
	public function toggle_subtask( $subtask_id, $is_completed ) {
		$subtask = $this->subtasks->find( $subtask_id );

		if ( ! $subtask ) {
			return false;
		}

		$task = $this->tasks ? $this->tasks->get_task_record( (int) $subtask['task_id'] ) : null;

		if ( ! $task || ! $this->permissions->can_update_task( $task ) ) {
			return false;
		}

		$this->subtasks->update(
			$subtask_id,
			array(
				'title'        => $subtask['title'],
				'is_completed' => $is_completed ? 1 : 0,
				'sort_order'   => (int) $subtask['sort_order'],
			)
		);

		$this->activity_log->log( 'subtask', $subtask_id, 'updated', 'Subtask updated.', array( 'task_id' => $subtask['task_id'] ) );

		return true;
	}

	/**
	 * Returns checklist summary for a task.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function summary_for_task( $task_id ) {
		$subtasks  = $this->get_subtasks( $task_id );
		$total     = count( $subtasks );
		$completed = count(
			array_filter(
				$subtasks,
				static function ( $subtask ) {
					return ! empty( $subtask['is_completed'] );
				}
			)
		);

		return array(
			'total'      => $total,
			'completed'  => $completed,
			'percentage' => $total > 0 ? (int) round( ( $completed / $total ) * 100 ) : 0,
		);
	}
}
