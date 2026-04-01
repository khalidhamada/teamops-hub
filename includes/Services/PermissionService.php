<?php
/**
 * Capability and visibility logic.
 *
 * @package TeamOpsHub\Services
 */

namespace TeamOpsHub\Services;

defined( 'ABSPATH' ) || exit;

class PermissionService {
	const ROLE_MANAGER = 'teamops_manager';
	const ROLE_MEMBER  = 'teamops_member';

	/**
	 * Registers plugin roles and capabilities.
	 *
	 * @return void
	 */
	public function register_roles() {
		add_role(
			self::ROLE_MANAGER,
			__( 'TeamOps Manager', 'teamops-hub' ),
			$this->manager_capabilities()
		);

		add_role(
			self::ROLE_MEMBER,
			__( 'TeamOps Member', 'teamops-hub' ),
			$this->member_capabilities()
		);

		$administrator = get_role( 'administrator' );

		if ( $administrator ) {
			foreach ( array_keys( $this->manager_capabilities() ) as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		update_option( 'teamops_hub_roles_version', TEAMOPS_HUB_VERSION );
	}

	/**
	 * Returns manager capabilities.
	 *
	 * @return array<string, bool>
	 */
	public function manager_capabilities() {
		return array(
			'read'                  => true,
			'teamops_access'        => true,
			'teamops_view_reports'  => true,
			'teamops_manage_settings' => true,
			'teamops_manage_projects' => true,
			'teamops_manage_tasks'    => true,
			'teamops_view_all_projects' => true,
			'teamops_view_all_tasks'    => true,
			'teamops_update_own_tasks'  => true,
		);
	}

	/**
	 * Returns member capabilities.
	 *
	 * @return array<string, bool>
	 */
	public function member_capabilities() {
		return array(
			'read'                    => true,
			'teamops_access'          => true,
			'teamops_view_member_area' => true,
			'teamops_update_own_tasks' => true,
		);
	}

	/**
	 * Checks whether current user can manage projects.
	 *
	 * @return bool
	 */
	public function can_manage_projects() {
		return current_user_can( 'teamops_manage_projects' );
	}

	/**
	 * Checks whether current user can manage tasks.
	 *
	 * @return bool
	 */
	public function can_manage_tasks() {
		return current_user_can( 'teamops_manage_tasks' );
	}

	/**
	 * Checks whether the user can update the task.
	 *
	 * @param array $task Task record.
	 * @return bool
	 */
	public function can_update_task( array $task ) {
		if ( $this->can_manage_tasks() ) {
			return true;
		}

		return current_user_can( 'teamops_update_own_tasks' ) && get_current_user_id() === (int) $task['assigned_user_id'];
	}
}
