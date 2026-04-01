<?php
/**
 * Date helper utilities.
 *
 * @package TeamOpsHub\Helpers
 */

namespace TeamOpsHub\Helpers;

defined( 'ABSPATH' ) || exit;

class DateHelper {
	/**
	 * Returns current mysql datetime in site timezone.
	 *
	 * @return string
	 */
	public static function now() {
		return current_time( 'mysql' );
	}

	/**
	 * Returns current date in site timezone.
	 *
	 * @return string
	 */
	public static function today() {
		return current_time( 'Y-m-d' );
	}
}
