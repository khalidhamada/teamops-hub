<?php
/**
 * Notification service placeholder.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Repositories\NotificationRepository;

defined( 'ABSPATH' ) || exit;

class NotificationService {
	/**
	 * Notification repository.
	 *
	 * @var NotificationRepository
	 */
	private $notifications;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->notifications = new NotificationRepository();
	}

	/**
	 * Dispatches a stub notification event for future modules.
	 *
	 * @param string $event Notification event.
	 * @param array  $payload Event payload.
	 * @return void
	 */
	public function dispatch( $event, array $payload = array() ) {
		do_action( 'teamops_hub_notification_event', $event, $payload );
	}

	/**
	 * Creates a persisted in-app notification.
	 *
	 * @param int    $user_id Recipient user id.
	 * @param string $type Notification type.
	 * @param string $title Title.
	 * @param string $body Message body.
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id Entity id.
	 * @param array  $context Optional context.
	 * @return int|false
	 */
	public function create( $user_id, $type, $title, $body, $entity_type, $entity_id, array $context = array() ) {
		$notification_id = $this->notifications->create(
			array(
				'user_id'     => absint( $user_id ),
				'type'        => sanitize_key( $type ),
				'title'       => sanitize_text_field( $title ),
				'body'        => sanitize_text_field( $body ),
				'entity_type' => sanitize_key( $entity_type ),
				'entity_id'   => absint( $entity_id ),
				'context'     => $context,
			)
		);

		if ( $notification_id ) {
			$this->dispatch(
				$type,
				array(
					'user_id'         => absint( $user_id ),
					'notification_id' => (int) $notification_id,
					'entity_type'     => sanitize_key( $entity_type ),
					'entity_id'       => absint( $entity_id ),
				)
			);
		}

		return $notification_id;
	}

	/**
	 * Returns recent notifications for current user.
	 *
	 * @param int  $limit Max rows.
	 * @param bool $unread_only Unread only.
	 * @return array
	 */
	public function recent_for_current_user( $limit = 8, $unread_only = false ) {
		return $this->notifications->for_user( get_current_user_id(), $limit, $unread_only );
	}

	/**
	 * Returns unread notification count for current user.
	 *
	 * @return int
	 */
	public function unread_count_for_current_user() {
		return $this->notifications->unread_count( get_current_user_id() );
	}

	/**
	 * Marks all current user notifications as read.
	 *
	 * @return bool
	 */
	public function mark_all_read_for_current_user() {
		return $this->notifications->mark_all_read( get_current_user_id() );
	}
}
