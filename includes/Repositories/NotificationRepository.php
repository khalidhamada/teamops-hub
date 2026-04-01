<?php
/**
 * Notification repository.
 *
 * @package TeamOpsHub\Repositories
 */

namespace TeamOpsHub\Repositories;

use TeamOpsHub\Helpers\DateHelper;

defined( 'ABSPATH' ) || exit;

class NotificationRepository extends BaseRepository {
	/**
	 * Creates a notification row.
	 *
	 * @param array $data Notification data.
	 * @return int|false
	 */
	public function create( array $data ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table( 'notifications' ),
			array(
				'user_id'     => (int) $data['user_id'],
				'type'        => $data['type'],
				'title'       => $data['title'],
				'body'        => $data['body'],
				'entity_type' => $data['entity_type'],
				'entity_id'   => (int) $data['entity_id'],
				'is_read'     => ! empty( $data['is_read'] ) ? 1 : 0,
				'read_at'     => ! empty( $data['read_at'] ) ? $data['read_at'] : null,
				'context'     => wp_json_encode( $data['context'] ?? array() ),
				'created_at'  => DateHelper::now(),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Returns notifications for a user.
	 *
	 * @param int  $user_id User id.
	 * @param int  $limit Result limit.
	 * @param bool $unread_only Limit to unread rows.
	 * @return array
	 */
	public function for_user( $user_id, $limit = 10, $unread_only = false ) {
		global $wpdb;

		$where = array( 'user_id = %d' );
		$args  = array( (int) $user_id );

		if ( $unread_only ) {
			$where[] = 'is_read = 0';
		}

		$args[] = max( 1, (int) $limit );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table( 'notifications' )} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT %d',
				$args
			),
			ARRAY_A
		);
	}

	/**
	 * Returns unread count for a user.
	 *
	 * @param int $user_id User id.
	 * @return int
	 */
	public function unread_count( $user_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM {$this->table( 'notifications' )} WHERE user_id = %d AND is_read = 0",
				$user_id
			)
		);
	}

	/**
	 * Marks notifications as read for a user.
	 *
	 * @param int $user_id User id.
	 * @return bool
	 */
	public function mark_all_read( $user_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table( 'notifications' ),
			array(
				'is_read' => 1,
				'read_at' => DateHelper::now(),
			),
			array(
				'user_id' => (int) $user_id,
				'is_read' => 0,
			),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);

		return false !== $result;
	}
}
