<?php
/**
 * Projects module wiring.
 *
 * @package TeamOpsHub\Modules\Projects
 */

namespace TeamOpsHub\Modules\Projects;

use TeamOpsHub\Modules\Projects\Admin\ProjectsPage;
use TeamOpsHub\Services\MilestoneService;
use TeamOpsHub\Services\PermissionService;
use TeamOpsHub\Services\ProjectService;

defined( 'ABSPATH' ) || exit;

class ProjectsModule {
	/**
	 * Project service.
	 *
	 * @var ProjectService
	 */
	private $projects;

	/**
	 * Permission service.
	 *
	 * @var PermissionService
	 */
	private $permissions;

	/**
	 * Milestone service.
	 *
	 * @var MilestoneService
	 */
	private $milestones;

	/**
	 * Admin page instance.
	 *
	 * @var ProjectsPage
	 */
	private $page;

	/**
	 * Constructor.
	 *
	 * @param ProjectService    $projects Project service.
	 * @param PermissionService $permissions Permission service.
	 * @param MilestoneService  $milestones Milestone service.
	 */
	public function __construct( ProjectService $projects, PermissionService $permissions, MilestoneService $milestones ) {
		$this->projects    = $projects;
		$this->permissions = $permissions;
		$this->milestones  = $milestones;
		$this->page        = new ProjectsPage( $this->projects, $this->permissions, $this->milestones );
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Registers projects submenu.
	 *
	 * @return void
	 */
	public function register_page() {
		if ( ! current_user_can( 'teamops_access' ) ) {
			return;
		}

		$this->page->register();
	}
}
