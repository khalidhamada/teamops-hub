<?php
/**
 * Shared repository helpers.
 *
 * @package TeamOpsHub\Repositories
 */

namespace TeamOpsHub\Repositories;

use TeamOpsHub\Database\SchemaManager;

defined( 'ABSPATH' ) || exit;

abstract class BaseRepository {
	/**
	 * Schema manager instance.
	 *
	 * @var SchemaManager
	 */
	protected $schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->schema = new SchemaManager();
	}

	/**
	 * Returns a table name.
	 *
	 * @param string $suffix Table suffix.
	 * @return string
	 */
	protected function table( $suffix ) {
		return $this->schema->table( $suffix );
	}
}
