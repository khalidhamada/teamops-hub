<?php
/**
 * Dashboard and reports placeholder page.
 *
 * @package TeamOpsHub\Admin
 */

namespace TeamOpsHub\Admin;

use TeamOpsHub\Services\PermissionService;
use TeamOpsHub\Services\ProjectService;
use TeamOpsHub\Services\TaskService;

defined( 'ABSPATH' ) || exit;

class DashboardPage {
	/**
	 * Project service.
	 *
	 * @var ProjectService
	 */
	private $projects;

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
	 * Constructor.
	 *
	 * @param ProjectService    $projects Project service.
	 * @param TaskService       $tasks Task service.
	 * @param PermissionService $permissions Permission service.
	 */
	public function __construct( ProjectService $projects, TaskService $tasks, PermissionService $permissions ) {
		$this->projects    = $projects;
		$this->tasks       = $tasks;
		$this->permissions = $permissions;
	}

	/**
	 * Renders dashboard.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'teamops_access' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		$project_counts = $this->projects->status_counts();
		$task_counts    = $this->tasks->status_counts();
		$my_tasks       = $this->tasks->get_tasks(
			array(
				'assigned_user_id' => get_current_user_id(),
			)
		);
		$overdue        = $this->tasks->overdue_tasks();
		$upcoming       = $this->tasks->upcoming_tasks();

		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'TeamOps Hub Dashboard', 'teamops-hub' ); ?></h1>
			<p><?php esc_html_e( 'Operational snapshot across projects and tasks.', 'teamops-hub' ); ?></p>
			<div class="teamops-grid">
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Projects by Status', 'teamops-hub' ); ?></h2>
					<?php $this->render_count_list( $project_counts ); ?>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Tasks by Status', 'teamops-hub' ); ?></h2>
					<?php $this->render_count_list( $task_counts ); ?>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Overdue Tasks', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( count( $overdue ) ); ?></p>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Upcoming Due Tasks', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( count( $upcoming ) ); ?></p>
				</div>
			</div>
			<div class="teamops-grid">
				<div class="teamops-card">
					<h2><?php esc_html_e( 'My Tasks', 'teamops-hub' ); ?></h2>
					<?php $this->render_task_table( $my_tasks ); ?>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Due Soon', 'teamops-hub' ); ?></h2>
					<?php $this->render_task_table( $upcoming ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders reports placeholder.
	 *
	 * @return void
	 */
	public function render_reports_placeholder() {
		if ( ! current_user_can( 'teamops_view_reports' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'Reports', 'teamops-hub' ); ?></h1>
			<div class="teamops-card">
				<p><?php esc_html_e( 'The reporting module is intentionally lightweight in MVP. This area is reserved for richer analytics, team performance dashboards, and operational KPIs in future releases.', 'teamops-hub' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a simple key/value list.
	 *
	 * @param array $counts Count rows.
	 * @return void
	 */
	private function render_count_list( array $counts ) {
		if ( empty( $counts ) ) {
			echo '<p>' . esc_html__( 'No data yet.', 'teamops-hub' ) . '</p>';
			return;
		}

		echo '<ul class="teamops-stat-list">';

		foreach ( $counts as $row ) {
			echo '<li><span>' . esc_html( ucfirst( str_replace( '_', ' ', $row['status'] ) ) ) . '</span><strong>' . esc_html( $row['total'] ) . '</strong></li>';
		}

		echo '</ul>';
	}

	/**
	 * Renders task summary table.
	 *
	 * @param array $tasks Task rows.
	 * @return void
	 */
	private function render_task_table( array $tasks ) {
		if ( empty( $tasks ) ) {
			echo '<p>' . esc_html__( 'No tasks found.', 'teamops-hub' ) . '</p>';
			return;
		}

		echo '<div class="teamops-table-wrap">';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'Task', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Status', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Due', 'teamops-hub' ) . '</th></tr></thead><tbody>';

		foreach ( $tasks as $task ) {
			echo '<tr>';
			echo '<td>' . esc_html( $task['title'] ) . '</td>';
			echo '<td>' . esc_html( $this->tasks->status_label( $task['status'] ) ) . '</td>';
			echo '<td>' . esc_html( $task['due_date'] ?: '—' ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}
}
