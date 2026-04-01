<?php
/**
 * Activity logging service.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Database\SchemaManager;
use TeamOpsHub\Helpers\DateHelper;

defined( 'ABSPATH' ) || exit;

class ActivityLogService {
	/**
	 * Logs a plugin activity item.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id Entity identifier.
	 * @param string $action Action name.
	 * @param string $description Human-readable summary.
	 * @param array  $context Optional context data.
	 * @return void
	 */
	public function log( $entity_type, $entity_id, $action, $description = '', array $context = array() ) {
		global $wpdb;

		$table = ( new SchemaManager() )->table( 'activity_log' );

		$wpdb->insert(
			$table,
			array(
				'entity_type' => sanitize_key( $entity_type ),
				'entity_id'   => absint( $entity_id ),
				'action'      => sanitize_key( $action ),
				'description' => sanitize_text_field( $description ),
				'context'     => wp_json_encode( $context ),
				'user_id'     => get_current_user_id(),
				'created_at'  => DateHelper::now(),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
	}
}
