<?php
/**
 * Task comments and mentions workflow.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Modules\Projects\Repositories\ProjectRepository;
use TeamOpsHub\Modules\Tasks\Repositories\TaskCommentRepository;

defined( 'ABSPATH' ) || exit;

class TaskCommentService {
	/**
	 * Comment repository.
	 *
	 * @var TaskCommentRepository
	 */
	private $comments;

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
	 * Notification service.
	 *
	 * @var NotificationService
	 */
	private $notifications;

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
	 * Constructor.
	 *
	 * @param ValidationService   $validation Validation service.
	 * @param TaskService         $tasks Task service.
	 * @param NotificationService $notifications Notification service.
	 * @param ActivityLogService  $activity_log Activity log service.
	 */
	public function __construct( ValidationService $validation, TaskService $tasks, NotificationService $notifications, ActivityLogService $activity_log ) {
		$this->comments      = new TaskCommentRepository();
		$this->validation    = $validation;
		$this->tasks         = $tasks;
		$this->notifications = $notifications;
		$this->activity_log  = $activity_log;
		$this->projects      = new ProjectRepository();
	}

	/**
	 * Returns comments for a visible task.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function get_comments( $task_id ) {
		$task = $this->tasks->get_task_record( $task_id );

		if ( ! $task ) {
			return array();
		}

		return $this->comments->for_task( $task_id );
	}

	/**
	 * Returns mentionable users for a visible task.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function mentionable_users_for_task( $task_id ) {
		$task = $this->tasks->get_task_record( $task_id );

		if ( ! $task ) {
			return array();
		}

		$user_ids = $this->participant_user_ids( $task );

		if ( empty( $user_ids ) ) {
			return array();
		}

		return get_users(
			array(
				'include' => $user_ids,
				'orderby' => 'display_name',
			)
		);
	}

	/**
	 * Saves a task comment and dispatches mention notifications.
	 *
	 * @param array $input Raw input.
	 * @return int|\WP_Error
	 */
	public function save_comment( array $input ) {
		$data   = $this->validation->sanitize_comment_data( $input );
		$errors = $this->validation->validate_comment_data( $data );

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'teamops_invalid_comment', implode( ' ', $errors ), $errors );
		}

		$task = $this->tasks->get_task_record( (int) $data['task_id'] );

		if ( ! $task ) {
			return new \WP_Error( 'teamops_invalid_comment_task', __( 'The selected task could not be found or is not visible to you.', 'teamops-hub' ) );
		}

		$data['user_id'] = get_current_user_id();
		$comment_id      = $this->comments->create( $data );

		if ( ! $comment_id ) {
			return new \WP_Error( 'teamops_comment_create_failed', __( 'The comment could not be saved.', 'teamops-hub' ) );
		}

		$mentions = $this->extract_mentions( $data['content'], $task );

		if ( ! empty( $mentions ) ) {
			$this->comments->create_mentions( $comment_id, $mentions );
			$this->dispatch_mentions( $mentions, $task, $comment_id );
		}

		$this->activity_log->log(
			'task',
			(int) $task['id'],
			'comment_added',
			'Task comment added.',
			array(
				'comment_id'      => $comment_id,
				'mention_count'   => count( $mentions ),
				'comment_excerpt' => substr( $data['content'], 0, 120 ),
			)
		);

		return (int) $comment_id;
	}

	/**
	 * Builds a hint string for mentionable users.
	 *
	 * @param int $task_id Task id.
	 * @return string
	 */
	public function mention_hint( $task_id ) {
		$users = $this->mentionable_users_for_task( $task_id );

		if ( empty( $users ) ) {
			return '';
		}

		$handles = array_map(
			static function ( $user ) {
				return '@' . $user->user_login;
			},
			$users
		);

		return implode( ', ', $handles );
	}

	/**
	 * Extracts valid mention targets from content.
	 *
	 * @param string $content Comment content.
	 * @param array  $task Visible task row.
	 * @return array
	 */
	private function extract_mentions( $content, array $task ) {
		preg_match_all( '/(^|[^a-zA-Z0-9_])@([a-zA-Z0-9._-]+)/', $content, $matches );

		if ( empty( $matches[2] ) ) {
			return array();
		}

		$mentionable = array();

		foreach ( $this->mentionable_users_for_task( (int) $task['id'] ) as $user ) {
			$mentionable[ strtolower( $user->user_login ) ] = $user;
		}

		$mentions = array();

		foreach ( array_unique( $matches[2] ) as $token ) {
			$lookup = strtolower( $token );

			if ( ! isset( $mentionable[ $lookup ] ) ) {
				continue;
			}

			$user = $mentionable[ $lookup ];

			if ( (int) $user->ID === get_current_user_id() ) {
				continue;
			}

			$mentions[] = array(
				'user_id' => (int) $user->ID,
				'token'   => sanitize_text_field( $token ),
				'user'    => $user,
			);
		}

		return $mentions;
	}

	/**
	 * Dispatches mention notifications and activity records.
	 *
	 * @param array $mentions Mention rows.
	 * @param array $task Task row.
	 * @param int   $comment_id Comment id.
	 * @return void
	 */
	private function dispatch_mentions( array $mentions, array $task, $comment_id ) {
		$current_user = wp_get_current_user();

		foreach ( $mentions as $mention ) {
			$this->notifications->create(
				$mention['user_id'],
				'mention',
				sprintf(
					/* translators: %s: task title */
					__( 'You were mentioned on "%s"', 'teamops-hub' ),
					$task['title']
				),
				sprintf(
					/* translators: 1: author display name, 2: mention token */
					__( '%1$s mentioned you in a task comment using @%2$s.', 'teamops-hub' ),
					$current_user->display_name ?: $current_user->user_login,
					$mention['token']
				),
				'task',
				(int) $task['id'],
				array(
					'comment_id' => (int) $comment_id,
					'task_id'    => (int) $task['id'],
				)
			);

			$this->activity_log->log(
				'task',
				(int) $task['id'],
				'mention_created',
				'Task comment mention created.',
				array(
					'comment_id'         => (int) $comment_id,
					'mentioned_user_id'  => (int) $mention['user_id'],
					'mention_token'      => $mention['token'],
				)
			);
		}
	}

	/**
	 * Builds participant ids for a task.
	 *
	 * @param array $task Task row.
	 * @return int[]
	 */
	private function participant_user_ids( array $task ) {
		$user_ids = array( (int) $task['assigned_user_id'] );
		$project  = $this->projects->find( (int) $task['project_id'] );

		if ( $project ) {
			$user_ids[] = (int) $project['owner_user_id'];
			$user_ids   = array_merge( $user_ids, $this->projects->member_ids( (int) $project['id'] ) );
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $user_ids ) ) ) );
	}
}
