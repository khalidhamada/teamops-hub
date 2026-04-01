<?php
/**
 * Plugin Name: TeamOps Hub
 * Plugin URI: https://example.com/teamops-hub
 * Description: Custom-table-first internal operations platform for project and task management.
 * Version: 0.4.0
 * Author: TeamOps Hub
 * Text Domain: teamops-hub
 * Domain Path: /languages
 *
 * @package TeamOpsHub
 */

defined( 'ABSPATH' ) || exit;

define( 'TEAMOPS_HUB_VERSION', '0.4.0' );
define( 'TEAMOPS_HUB_DB_VERSION', '1.3.0' );
define( 'TEAMOPS_HUB_FILE', __FILE__ );
define( 'TEAMOPS_HUB_PATH', plugin_dir_path( __FILE__ ) );
define( 'TEAMOPS_HUB_URL', plugin_dir_url( __FILE__ ) );
define( 'TEAMOPS_HUB_BASENAME', plugin_basename( __FILE__ ) );

require_once TEAMOPS_HUB_PATH . 'includes/Core/Autoloader.php';

\TeamOpsHub\Core\Autoloader::register();

register_activation_hook( TEAMOPS_HUB_FILE, array( '\TeamOpsHub\Core\Installer', 'activate' ) );
register_deactivation_hook( TEAMOPS_HUB_FILE, array( '\TeamOpsHub\Core\Installer', 'deactivate' ) );

function teamops_hub() {
	return \TeamOpsHub\Core\Plugin::instance();
}

teamops_hub()->boot();
