<?php
/**
 * Uninstall TeamOps Hub.
 *
 * @package TeamOpsHub
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'teamops_hub_db_version' );
delete_option( 'teamops_hub_settings' );
delete_option( 'teamops_hub_roles_version' );
