<?php
/**
 * Plugin lifecycle handlers.
 *
 * @package TeamOpsHub\Core
 */

namespace TeamOpsHub\Core;

use TeamOpsHub\Database\SchemaManager;
use TeamOpsHub\Services\PermissionService;

defined( 'ABSPATH' ) || exit;

class Installer {
	/**
	 * Runs activation logic.
	 *
	 * @return void
	 */
	public static function activate() {
		( new SchemaManager() )->migrate();
		( new PermissionService() )->register_roles();
		flush_rewrite_rules();
	}

	/**
	 * Runs deactivation logic.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
