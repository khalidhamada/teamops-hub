<?php
/**
 * Validation and normalization for plugin entities.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

defined( 'ABSPATH' ) || exit;

class ValidationService {
	/**
	 * Validates project input.
	 *
	 * @param array $data Sanitized project data.
	 * @return string[]
	 */
	public function validate_project_data( array $data ) {
		$errors = array();

		if ( '' === $data['title'] ) {
			$errors[] = __( 'Project title is required.', 'teamops-hub' );
		}

		if ( empty( $data['owner_user_id'] ) ) {
			$errors[] = __( 'A project owner is required.', 'teamops-hub' );
		}

		if ( ! $this->is_valid_date_range( $data['start_date'], $data['due_date'] ) ) {
			$errors[] = __( 'Project due date must be on or after the start date.', 'teamops-hub' );
		}

		return $errors;
	}

	/**
	 * Sanitizes project input.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public function sanitize_project_data( array $data ) {
		return array(
			'title'       => sanitize_text_field( $data['title'] ?? '' ),
			'code'        => sanitize_text_field( $data['code'] ?? '' ),
			'description' => wp_kses_post( $data['description'] ?? '' ),
			'status'      => sanitize_key( $data['status'] ?? 'planned' ),
			'priority'    => sanitize_key( $data['priority'] ?? 'medium' ),
			'owner_user_id' => absint( $data['owner_user_id'] ?? 0 ),
			'project_type'  => sanitize_text_field( $data['project_type'] ?? '' ),
			'start_date'    => sanitize_text_field( $data['start_date'] ?? '' ),
			'due_date'      => sanitize_text_field( $data['due_date'] ?? '' ),
			'notes'         => wp_kses_post( $data['notes'] ?? '' ),
			'member_ids'    => array_values( array_unique( array_filter( array_map( 'absint', (array) ( $data['member_ids'] ?? array() ) ) ) ) ),
		);
	}

	/**
	 * Sanitizes task input.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public function sanitize_task_data( array $data ) {
		return array(
			'project_id'      => absint( $data['project_id'] ?? 0 ),
			'title'           => sanitize_text_field( $data['title'] ?? '' ),
			'description'     => wp_kses_post( $data['description'] ?? '' ),
			'status'          => sanitize_key( $data['status'] ?? 'todo' ),
			'priority'        => sanitize_key( $data['priority'] ?? 'medium' ),
			'milestone_id'    => absint( $data['milestone_id'] ?? 0 ),
			'assigned_user_id' => absint( $data['assigned_user_id'] ?? 0 ),
			'start_date'      => sanitize_text_field( $data['start_date'] ?? '' ),
			'due_date'        => sanitize_text_field( $data['due_date'] ?? '' ),
			'estimated_hours' => isset( $data['estimated_hours'] ) ? floatval( $data['estimated_hours'] ) : null,
			'actual_hours'    => isset( $data['actual_hours'] ) ? floatval( $data['actual_hours'] ) : null,
		);
	}

	/**
	 * Validates task input.
	 *
	 * @param array $data Sanitized task data.
	 * @param int[] $allowed_assignee_ids Allowed assignee ids for the selected project.
	 * @return string[]
	 */
	public function validate_task_data( array $data, array $allowed_assignee_ids = array() ) {
		$errors = array();

		if ( empty( $data['project_id'] ) ) {
			$errors[] = __( 'A project must be selected for the task.', 'teamops-hub' );
		}

		if ( '' === $data['title'] ) {
			$errors[] = __( 'Task title is required.', 'teamops-hub' );
		}

		if ( empty( $data['assigned_user_id'] ) ) {
			$errors[] = __( 'An assignee is required.', 'teamops-hub' );
		}

		if ( ! $this->is_valid_date_range( $data['start_date'], $data['due_date'] ) ) {
			$errors[] = __( 'Task due date must be on or after the start date.', 'teamops-hub' );
		}

		if ( ! empty( $data['assigned_user_id'] ) && ! empty( $allowed_assignee_ids ) && ! in_array( (int) $data['assigned_user_id'], array_map( 'intval', $allowed_assignee_ids ), true ) ) {
			$errors[] = __( 'The assigned user must belong to the selected project team.', 'teamops-hub' );
		}

		return $errors;
	}

	/**
	 * Sanitizes task status data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public function sanitize_task_status_data( array $data ) {
		$status_key = sanitize_key( $data['status_key'] ?? '' );

		return array(
			'status_key' => $status_key,
			'label'      => sanitize_text_field( $data['label'] ?? '' ),
			'color'      => sanitize_hex_color( $data['color'] ?? '' ) ?: '#2271b1',
			'sort_order' => absint( $data['sort_order'] ?? 0 ),
			'is_default' => ! empty( $data['is_default'] ) ? 1 : 0,
			'is_closed'  => ! empty( $data['is_closed'] ) ? 1 : 0,
			'is_active'  => ! isset( $data['is_active'] ) || ! empty( $data['is_active'] ) ? 1 : 0,
		);
	}

	/**
	 * Validates task status data.
	 *
	 * @param array $data Sanitized data.
	 * @param array $existing_options Existing options keyed by status key.
	 * @return string[]
	 */
	public function validate_task_status_data( array $data, array $existing_options = array() ) {
		$errors = array();

		if ( '' === $data['status_key'] ) {
			$errors[] = __( 'Task status key is required.', 'teamops-hub' );
		}

		if ( '' === $data['label'] ) {
			$errors[] = __( 'Task status label is required.', 'teamops-hub' );
		}

		if ( ! preg_match( '/^[a-z0-9_]+$/', $data['status_key'] ) ) {
			$errors[] = __( 'Task status key may only contain lowercase letters, numbers, and underscores.', 'teamops-hub' );
		}

		if ( isset( $existing_options[ $data['status_key'] ] ) && $data['label'] !== $existing_options[ $data['status_key'] ] ) {
			return $errors;
		}

		return $errors;
	}

	/**
	 * Sanitizes milestone data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public function sanitize_milestone_data( array $data ) {
		return array(
			'project_id'   => absint( $data['project_id'] ?? 0 ),
			'title'        => sanitize_text_field( $data['title'] ?? '' ),
			'description'  => wp_kses_post( $data['description'] ?? '' ),
			'status'       => sanitize_key( $data['status'] ?? 'planned' ),
			'due_date'     => sanitize_text_field( $data['due_date'] ?? '' ),
			'sort_order'   => absint( $data['sort_order'] ?? 0 ),
		);
	}

	/**
	 * Validates milestone data.
	 *
	 * @param array $data Sanitized milestone data.
	 * @param array $allowed_statuses Allowed milestone statuses.
	 * @return string[]
	 */
	public function validate_milestone_data( array $data, array $allowed_statuses = array() ) {
		$errors = array();

		if ( empty( $data['project_id'] ) ) {
			$errors[] = __( 'A project is required for the milestone.', 'teamops-hub' );
		}

		if ( '' === $data['title'] ) {
			$errors[] = __( 'Milestone title is required.', 'teamops-hub' );
		}

		if ( ! empty( $allowed_statuses ) && ! in_array( $data['status'], $allowed_statuses, true ) ) {
			$errors[] = __( 'The selected milestone status is not valid.', 'teamops-hub' );
		}

		if ( ! $this->is_valid_date( $data['due_date'] ) ) {
			$errors[] = __( 'Milestone due date must be a valid date.', 'teamops-hub' );
		}

		return $errors;
	}

	/**
	 * Sanitizes subtask data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public function sanitize_subtask_data( array $data ) {
		return array(
			'task_id'       => absint( $data['task_id'] ?? 0 ),
			'title'         => sanitize_text_field( $data['title'] ?? '' ),
			'is_completed'  => ! empty( $data['is_completed'] ) ? 1 : 0,
			'sort_order'    => absint( $data['sort_order'] ?? 0 ),
		);
	}

	/**
	 * Validates subtask data.
	 *
	 * @param array $data Sanitized subtask data.
	 * @return string[]
	 */
	public function validate_subtask_data( array $data ) {
		$errors = array();

		if ( empty( $data['task_id'] ) ) {
			$errors[] = __( 'A parent task is required.', 'teamops-hub' );
		}

		if ( '' === $data['title'] ) {
			$errors[] = __( 'Subtask title is required.', 'teamops-hub' );
		}

		return $errors;
	}

	/**
	 * Sanitizes comment data.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public function sanitize_comment_data( array $data ) {
		return array(
			'task_id'           => absint( $data['task_id'] ?? 0 ),
			'content'           => sanitize_textarea_field( $data['content'] ?? '' ),
			'parent_comment_id' => absint( $data['parent_comment_id'] ?? 0 ),
		);
	}

	/**
	 * Validates comment data.
	 *
	 * @param array $data Sanitized comment data.
	 * @return string[]
	 */
	public function validate_comment_data( array $data ) {
		$errors = array();

		if ( empty( $data['task_id'] ) ) {
			$errors[] = __( 'A task is required for the comment.', 'teamops-hub' );
		}

		if ( '' === trim( $data['content'] ) ) {
			$errors[] = __( 'Comment content is required.', 'teamops-hub' );
		}

		return $errors;
	}

	/**
	 * Returns project statuses.
	 *
	 * @return array<string, string>
	 */
	public function project_statuses() {
		return array(
			'planned'   => __( 'Planned', 'teamops-hub' ),
			'active'    => __( 'Active', 'teamops-hub' ),
			'on_hold'   => __( 'On Hold', 'teamops-hub' ),
			'completed' => __( 'Completed', 'teamops-hub' ),
			'archived'  => __( 'Archived', 'teamops-hub' ),
		);
	}

	/**
	 * Returns task statuses.
	 *
	 * @return array<string, string>
	 */
	public function task_statuses() {
		return array(
			'todo'        => __( 'To Do', 'teamops-hub' ),
			'in_progress' => __( 'In Progress', 'teamops-hub' ),
			'blocked'     => __( 'Blocked', 'teamops-hub' ),
			'review'      => __( 'Review', 'teamops-hub' ),
			'done'        => __( 'Done', 'teamops-hub' ),
		);
	}

	/**
	 * Returns priority options.
	 *
	 * @return array<string, string>
	 */
	public function priorities() {
		return array(
			'low'      => __( 'Low', 'teamops-hub' ),
			'medium'   => __( 'Medium', 'teamops-hub' ),
			'high'     => __( 'High', 'teamops-hub' ),
			'critical' => __( 'Critical', 'teamops-hub' ),
		);
	}

	/**
	 * Checks whether provided dates form a valid range.
	 *
	 * @param string $start_date Start date.
	 * @param string $due_date Due date.
	 * @return bool
	 */
	private function is_valid_date_range( $start_date, $due_date ) {
		if ( '' === $start_date || '' === $due_date ) {
			return true;
		}

		return strtotime( $due_date ) >= strtotime( $start_date );
	}

	/**
	 * Checks whether a single date string is valid.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private function is_valid_date( $date ) {
		if ( '' === $date ) {
			return true;
		}

		$parsed = \DateTime::createFromFormat( 'Y-m-d', $date );

		return $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}
}
