<?php
/**
 * Simple PSR-4 style autoloader for plugin classes.
 *
 * @package TeamOpsHub\Core
 */

namespace TeamOpsHub\Core;

defined( 'ABSPATH' ) || exit;

class Autoloader {
	/**
	 * Registers the autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Attempts to load a class file.
	 *
	 * @param string $class_name Full class name.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		$prefix = 'TeamOpsHub\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$relative_path  = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';
		$file           = TEAMOPS_HUB_PATH . 'includes' . DIRECTORY_SEPARATOR . $relative_path;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
