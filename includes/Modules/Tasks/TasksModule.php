<?php
/**
 * Tasks module wiring.
 *
 * @package TeamOpsHub\Modules\Tasks
 */

namespace TeamOpsHub\Modules\Tasks;

use TeamOpsHub\Modules\Tasks\Admin\TasksPage;
use TeamOpsHub\Services\MilestoneService;
use TeamOpsHub\Services\PermissionService;
use TeamOpsHub\Services\ProjectService;
use TeamOpsHub\Services\SubtaskService;
use TeamOpsHub\Services\TaskCommentService;
use TeamOpsHub\Services\TaskService;

defined( 'ABSPATH' ) || exit;

class TasksModule {
	/**
	 * Task service.
	 *
	 * @var TaskService
	 */
	private $tasks;

	/**
	 * Permission service.
	 *
	 * @var PermissionService
	 */
	private $permissions;

	/**
	 * Project service.
	 *
	 * @var ProjectService
	 */
	private $projects;

	/**
	 * Milestone service.
	 *
	 * @var MilestoneService
	 */
	private $milestones;

	/**
	 * Subtask service.
	 *
	 * @var SubtaskService
	 */
	private $subtasks;

	/**
	 * Comment service.
	 *
	 * @var TaskCommentService
	 */
	private $comments;

	/**
	 * Admin page instance.
	 *
	 * @var TasksPage
	 */
	private $page;

	/**
	 * Constructor.
	 *
	 * @param TaskService       $tasks Task service.
	 * @param PermissionService $permissions Permission service.
	 * @param ProjectService    $projects Project service.
	 * @param MilestoneService  $milestones Milestone service.
	 * @param SubtaskService    $subtasks Subtask service.
	 * @param TaskCommentService $comments Comment service.
	 */
	public function __construct( TaskService $tasks, PermissionService $permissions, ProjectService $projects, MilestoneService $milestones, SubtaskService $subtasks, TaskCommentService $comments ) {
		$this->tasks       = $tasks;
		$this->permissions = $permissions;
		$this->projects    = $projects;
		$this->milestones  = $milestones;
		$this->subtasks    = $subtasks;
		$this->comments    = $comments;
		$this->page        = new TasksPage( $this->tasks, $this->projects, $this->permissions, $this->milestones, $this->subtasks, $this->comments );
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
	 * Registers tasks submenu.
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
