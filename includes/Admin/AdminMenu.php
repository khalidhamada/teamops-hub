<?php
/**
 * Plugin admin navigation.
 *
 * @package TeamOpsHub\Admin
 */

namespace TeamOpsHub\Admin;

defined( 'ABSPATH' ) || exit;

class AdminMenu {
	/**
	 * Dashboard page.
	 *
	 * @var DashboardPage
	 */
	private $dashboard_page;

	/**
	 * Member page.
	 *
	 * @var MemberPage
	 */
	private $member_page;

	/**
	 * Settings page.
	 *
	 * @var SettingsPage
	 */
	private $settings_page;

	/**
	 * Constructor.
	 *
	 * @param DashboardPage $dashboard_page Dashboard page.
	 * @param MemberPage    $member_page Member page.
	 * @param SettingsPage  $settings_page Settings page.
	 */
	public function __construct( DashboardPage $dashboard_page, MemberPage $member_page, SettingsPage $settings_page ) {
		$this->dashboard_page = $dashboard_page;
		$this->member_page    = $member_page;
		$this->settings_page  = $settings_page;
	}

	/**
	 * Registers top-level menu and shared pages.
	 *
	 * @return void
	 */
	public function register() {
		add_menu_page(
			__( 'TeamOps Hub', 'teamops-hub' ),
			__( 'TeamOps Hub', 'teamops-hub' ),
			'teamops_access',
			'teamops-hub',
			array( $this->dashboard_page, 'render' ),
			'dashicons-groups',
			26
		);

		add_submenu_page(
			'teamops-hub',
			__( 'Dashboard', 'teamops-hub' ),
			__( 'Dashboard', 'teamops-hub' ),
			'teamops_access',
			'teamops-hub',
			array( $this->dashboard_page, 'render' )
		);

		add_submenu_page(
			'teamops-hub',
			__( 'My Work', 'teamops-hub' ),
			__( 'My Work', 'teamops-hub' ),
			'teamops_access',
			'teamops-hub-my-work',
			array( $this->member_page, 'render' )
		);

		add_submenu_page(
			'teamops-hub',
			__( 'Reports', 'teamops-hub' ),
			__( 'Reports', 'teamops-hub' ),
			'teamops_view_reports',
			'teamops-hub-reports',
			array( $this->dashboard_page, 'render_reports_placeholder' )
		);

		add_submenu_page(
			'teamops-hub',
			__( 'Future Modules', 'teamops-hub' ),
			__( 'Future Modules', 'teamops-hub' ),
			'teamops_manage_settings',
			'teamops-hub-future-modules',
			array( $this->settings_page, 'render_modules_placeholder' )
		);

		add_submenu_page(
			'teamops-hub',
			__( 'Settings', 'teamops-hub' ),
			__( 'Settings', 'teamops-hub' ),
			'teamops_manage_settings',
			'teamops-hub-settings',
			array( $this->settings_page, 'render' )
		);
	}
}
