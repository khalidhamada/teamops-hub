<?php
/**
 * Core plugin bootstrap and service registry.
 *
 * @package TeamOpsHub\Core
 */

namespace TeamOpsHub\Core;

use TeamOpsHub\Admin\AdminAssets;
use TeamOpsHub\Admin\AdminMenu;
use TeamOpsHub\Admin\DashboardPage;
use TeamOpsHub\Admin\MemberPage;
use TeamOpsHub\Admin\SettingsPage;
use TeamOpsHub\Database\SchemaManager;
use TeamOpsHub\Frontend\WorkspaceShortcode;
use TeamOpsHub\Modules\Projects\ProjectsModule;
use TeamOpsHub\Modules\Tasks\TasksModule;
use TeamOpsHub\Services\ActivityLogService;
use TeamOpsHub\Services\NotificationService;
use TeamOpsHub\Services\PermissionService;
use TeamOpsHub\Services\ProjectService;
use TeamOpsHub\Services\TaskCommentService;
use TeamOpsHub\Services\TaskService;
use TeamOpsHub\Services\SubtaskService;
use TeamOpsHub\Services\TaskStatusService;
use TeamOpsHub\Services\MilestoneService;
use TeamOpsHub\Services\ValidationService;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance;

	/**
	 * Loaded services.
	 *
	 * @var array<string, object>
	 */
	private $services = array();

	/**
	 * Registered admin page objects.
	 *
	 * @var array<string, object>
	 */
	private $admin_pages = array();

	/**
	 * Front-end components.
	 *
	 * @var array<string, object>
	 */
	private $frontend_components = array();

	/**
	 * Whether boot has run.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Returns singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstraps plugin hooks and services.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_services' ), 5 );
		add_action( 'init', array( $this, 'maybe_upgrade' ), 10 );
		add_action( 'init', array( $this, 'register_modules' ), 15 );
		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Loads text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'teamops-hub', false, dirname( TEAMOPS_HUB_BASENAME ) . '/languages' );
	}

	/**
	 * Registers shared services.
	 *
	 * @return void
	 */
	public function register_services() {
		$this->services['schema_manager']     = new SchemaManager();
		$this->services['permission_service'] = new PermissionService();
		$this->services['validation_service'] = new ValidationService();
		$this->services['activity_log']       = new ActivityLogService();
		$this->services['notification']       = new NotificationService();
		$this->services['task_status_service'] = new TaskStatusService(
			$this->services['validation_service']
		);
		$this->services['milestone_service'] = new MilestoneService(
			$this->services['permission_service'],
			$this->services['validation_service'],
			$this->services['activity_log']
		);
		$this->services['subtask_service'] = new SubtaskService(
			$this->services['validation_service'],
			$this->services['permission_service'],
			$this->services['activity_log']
		);
		$this->services['project_service']    = new ProjectService(
			$this->services['permission_service'],
			$this->services['validation_service'],
			$this->services['activity_log']
		);
		$this->services['task_service']       = new TaskService(
			$this->services['permission_service'],
			$this->services['validation_service'],
			$this->services['activity_log'],
			$this->services['task_status_service'],
			$this->services['milestone_service'],
			$this->services['subtask_service']
		);
		$this->services['task_comment_service'] = new TaskCommentService(
			$this->services['validation_service'],
			$this->services['task_service'],
			$this->services['notification'],
			$this->services['activity_log']
		);
		$this->services['subtask_service']->set_task_service( $this->services['task_service'] );

		$this->admin_pages['dashboard'] = new DashboardPage(
			$this->get( 'project_service' ),
			$this->get( 'task_service' ),
			$this->get( 'permission_service' )
		);
		$this->admin_pages['member']    = new MemberPage(
			$this->get( 'project_service' ),
			$this->get( 'task_service' ),
			$this->get( 'subtask_service' ),
			$this->get( 'task_comment_service' ),
			$this->get( 'notification' )
		);
		$this->admin_pages['settings']  = new SettingsPage(
			$this->get( 'task_status_service' )
		);
		$this->frontend_components['workspace'] = new WorkspaceShortcode(
			$this->get( 'project_service' ),
			$this->get( 'task_service' ),
			$this->get( 'subtask_service' ),
			$this->get( 'task_comment_service' ),
			$this->get( 'notification' )
		);
	}

	/**
	 * Ensures schema and roles are current after plugin updates.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		if ( TEAMOPS_HUB_DB_VERSION !== get_option( 'teamops_hub_db_version' ) ) {
			$this->get( 'schema_manager' )->migrate();
		}

		if ( TEAMOPS_HUB_VERSION !== get_option( 'teamops_hub_roles_version' ) ) {
			$this->get( 'permission_service' )->register_roles();
		}
	}

	/**
	 * Registers plugin modules.
	 *
	 * @return void
	 */
	public function register_modules() {
		$projects_module = new ProjectsModule(
			$this->get( 'project_service' ),
			$this->get( 'permission_service' ),
			$this->get( 'milestone_service' )
		);
		$tasks_module    = new TasksModule(
			$this->get( 'task_service' ),
			$this->get( 'permission_service' ),
			$this->get( 'project_service' ),
			$this->get( 'milestone_service' ),
			$this->get( 'subtask_service' ),
			$this->get( 'task_comment_service' )
		);

		$projects_module->register();
		$tasks_module->register();
	}

	/**
	 * Registers admin pages.
	 *
	 * @return void
	 */
	public function register_admin_pages() {
		$menu = new AdminMenu(
			$this->admin_pages['dashboard'],
			$this->admin_pages['member'],
			$this->admin_pages['settings']
		);

		$menu->register();
	}

	/**
	 * Enqueues shared admin assets.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		( new AdminAssets() )->enqueue();
	}

	/**
	 * Returns a registered service.
	 *
	 * @param string $service_id Service identifier.
	 * @return object|null
	 */
	public function get( $service_id ) {
		return $this->services[ $service_id ] ?? null;
	}
}
