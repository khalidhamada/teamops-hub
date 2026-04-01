<?php
/**
 * Task comment repository.
 *
 * @package TeamOpsHub\Modules\Tasks\Repositories
 */

namespace TeamOpsHub\Modules\Tasks\Repositories;

use TeamOpsHub\Helpers\DateHelper;
use TeamOpsHub\Repositories\BaseRepository;

defined( 'ABSPATH' ) || exit;

class TaskCommentRepository extends BaseRepository {
	/**
	 * Returns comments for a task.
	 *
	 * @param int $task_id Task id.
	 * @return array
	 */
	public function for_task( $task_id ) {
		global $wpdb;

		$comments_table = $this->table( 'task_comments' );
		$users_table    = $wpdb->users;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.*, u.display_name, u.user_login
				FROM {$comments_table} c
				LEFT JOIN {$users_table} u ON u.ID = c.user_id
				WHERE c.task_id = %d
				ORDER BY c.created_at ASC, c.id ASC",
				$task_id
			),
			ARRAY_A
		);
	}

	/**
	 * Creates a comment.
	 *
	 * @param array $data Comment data.
	 * @return int|false
	 */
	public function create( array $data ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table( 'task_comments' ),
			array(
				'task_id'           => (int) $data['task_id'],
				'user_id'           => (int) $data['user_id'],
				'content'           => $data['content'],
				'parent_comment_id' => ! empty( $data['parent_comment_id'] ) ? (int) $data['parent_comment_id'] : null,
				'created_at'        => DateHelper::now(),
				'updated_at'        => DateHelper::now(),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Stores comment mentions.
	 *
	 * @param int   $comment_id Comment id.
	 * @param array $mentions Mention rows.
	 * @return void
	 */
	public function create_mentions( $comment_id, array $mentions ) {
		global $wpdb;

		foreach ( $mentions as $mention ) {
			$wpdb->insert(
				$this->table( 'task_comment_mentions' ),
				array(
					'comment_id'         => (int) $comment_id,
					'mentioned_user_id'  => (int) $mention['user_id'],
					'mention_token'      => $mention['token'],
					'created_at'         => DateHelper::now(),
				),
				array( '%d', '%d', '%s', '%s' )
			);
		}
	}
}
