<?php
/**
 * Task status business logic.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

use TeamOpsHub\Modules\Tasks\Repositories\TaskStatusRepository;

defined( 'ABSPATH' ) || exit;

class TaskStatusService {
	/**
	 * Repository.
	 *
	 * @var TaskStatusRepository
	 */
	private $statuses;

	/**
	 * Validation service.
	 *
	 * @var ValidationService
	 */
	private $validation;

	/**
	 * Constructor.
	 *
	 * @param ValidationService $validation Validation service.
	 */
	public function __construct( ValidationService $validation ) {
		$this->statuses   = new TaskStatusRepository();
		$this->validation = $validation;
	}

	/**
	 * Returns task status rows.
	 *
	 * @return array
	 */
	public function all() {
		return $this->statuses->all_active();
	}

	/**
	 * Returns task status options keyed by status key.
	 *
	 * @return array
	 */
	public function options() {
		$options = array();

		foreach ( $this->all() as $status ) {
			$options[ $status['status_key'] ] = $status['label'];
		}

		return $options;
	}

	/**
	 * Returns one status label.
	 *
	 * @param string $status_key Status key.
	 * @return string
	 */
	public function label( $status_key ) {
		$status = $this->statuses->find_by_key( $status_key );

		if ( $status ) {
			return $status['label'];
		}

		return ucwords( str_replace( '_', ' ', sanitize_key( $status_key ) ) );
	}

	/**
	 * Returns closed status keys.
	 *
	 * @return array
	 */
	public function closed_keys() {
		$keys = array();

		foreach ( $this->all() as $status ) {
			if ( ! empty( $status['is_closed'] ) ) {
				$keys[] = $status['status_key'];
			}
		}

		return $keys;
	}

	/**
	 * Checks whether a status key is treated as closed.
	 *
	 * @param string $status_key Status key.
	 * @return bool
	 */
	public function is_closed( $status_key ) {
		return in_array( sanitize_key( $status_key ), $this->closed_keys(), true );
	}

	/**
	 * Saves a custom status.
	 *
	 * @param array $input Raw input.
	 * @return int|\WP_Error
	 */
	public function save_status( array $input ) {
		$data = $this->validation->sanitize_task_status_data( $input );
		$errors = $this->validation->validate_task_status_data( $data, $this->options() );

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'teamops_invalid_task_status', implode( ' ', $errors ), $errors );
		}

		$existing = $this->statuses->find_by_key( $data['status_key'] );

		if ( $existing ) {
			$this->statuses->update( (int) $existing['id'], $data );
			return (int) $existing['id'];
		}

		return $this->statuses->create( $data );
	}

	/**
	 * Returns a valid task status key.
	 *
	 * @param string $status_key Requested status key.
	 * @return string
	 */
	public function normalize_status_key( $status_key ) {
		$status_key = sanitize_key( $status_key );
		$options    = $this->options();

		if ( isset( $options[ $status_key ] ) ) {
			return $status_key;
		}

		return array_key_first( $options ) ?: 'todo';
	}
}
