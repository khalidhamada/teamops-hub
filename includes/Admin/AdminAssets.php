<?php
/**
 * Shared admin asset registration.
 *
 * @package TeamOpsHub\Admin
 */

namespace TeamOpsHub\Admin;

defined( 'ABSPATH' ) || exit;

class AdminAssets {
	/**
	 * Enqueues admin assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_style(
			'teamops-hub-admin',
			TEAMOPS_HUB_URL . 'assets/css/admin.css',
			array(),
			TEAMOPS_HUB_VERSION
		);

		wp_enqueue_script(
			'teamops-hub-admin',
			TEAMOPS_HUB_URL . 'assets/js/admin.js',
			array(),
			TEAMOPS_HUB_VERSION,
			true
		);
	}
}
