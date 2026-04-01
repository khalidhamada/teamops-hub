<?php
/**
 * Tasks admin page.
 *
 * @package TeamOpsHub\Modules\Tasks\Admin
 */

namespace TeamOpsHub\Modules\Tasks\Admin;

use TeamOpsHub\Services\MilestoneService;
use TeamOpsHub\Services\PermissionService;
use TeamOpsHub\Services\ProjectService;
use TeamOpsHub\Services\SubtaskService;
use TeamOpsHub\Services\TaskCommentService;
use TeamOpsHub\Services\TaskService;

defined( 'ABSPATH' ) || exit;

class TasksPage {
	/**
	 * Task service.
	 *
	 * @var TaskService
	 */
	private $tasks;

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
	 * Constructor.
	 *
	 * @param TaskService        $tasks Task service.
	 * @param ProjectService     $projects Project service.
	 * @param PermissionService  $permissions Permission service.
	 * @param MilestoneService   $milestones Milestone service.
	 * @param SubtaskService     $subtasks Subtask service.
	 * @param TaskCommentService $comments Comment service.
	 */
	public function __construct( TaskService $tasks, ProjectService $projects, PermissionService $permissions, MilestoneService $milestones, SubtaskService $subtasks, TaskCommentService $comments ) {
		$this->tasks       = $tasks;
		$this->projects    = $projects;
		$this->permissions = $permissions;
		$this->milestones  = $milestones;
		$this->subtasks    = $subtasks;
		$this->comments    = $comments;

		add_action( 'admin_post_teamops_hub_save_task', array( $this, 'handle_save' ) );
		add_action( 'admin_post_teamops_hub_save_subtask', array( $this, 'handle_save_subtask' ) );
		add_action( 'admin_post_teamops_hub_toggle_subtask', array( $this, 'handle_toggle_subtask' ) );
		add_action( 'admin_post_teamops_hub_save_task_comment', array( $this, 'handle_save_comment' ) );
	}

	/**
	 * Registers submenu.
	 *
	 * @return void
	 */
	public function register() {
		add_submenu_page(
			'teamops-hub',
			__( 'Tasks', 'teamops-hub' ),
			__( 'Tasks', 'teamops-hub' ),
			'teamops_access',
			'teamops-hub-tasks',
			array( $this, 'render' )
		);
	}

	/**
	 * Renders task page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'teamops_access' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		$task_id       = absint( $_GET['task_id'] ?? 0 );
		$task          = $task_id ? $this->tasks->get_task( $task_id ) : null;
		$options       = $this->tasks->form_options();
		$filters       = array(
			'search'           => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'project_id'       => absint( $_GET['filter_project_id'] ?? 0 ),
			'milestone_id'     => absint( $_GET['filter_milestone_id'] ?? 0 ),
			'status'           => sanitize_key( $_GET['filter_status'] ?? '' ),
			'assigned_user_id' => absint( $_GET['filter_assigned_user_id'] ?? 0 ),
			'priority'         => sanitize_key( $_GET['filter_priority'] ?? '' ),
			'due_bucket'       => sanitize_key( $_GET['filter_due_bucket'] ?? '' ),
		);
		$tasks         = $this->tasks->get_tasks( $this->active_filters( $filters ) );
		$user_map      = $this->user_map( $options['users'] );
		$project_map   = $this->project_map( $options['projects'] );
		$milestone_map = $this->milestone_map( $options['milestones'] );
		$status_map    = $options['statuses'];
		$metrics       = $this->task_metrics( $tasks );
		$subtasks      = $task ? $this->subtasks->get_subtasks( (int) $task['id'] ) : array();
		$checklist     = $task ? $task['checklist_summary'] : array( 'total' => 0, 'completed' => 0, 'percentage' => 0 );
		$comments      = $task ? $this->comments->get_comments( (int) $task['id'] ) : array();
		$mention_hint  = $task ? $this->comments->mention_hint( (int) $task['id'] ) : '';

		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'Tasks', 'teamops-hub' ); ?></h1>
			<p><?php esc_html_e( 'Track execution, unblock work early, and keep delivery ownership clear.', 'teamops-hub' ); ?></p>
			<?php $this->render_notices(); ?>
			<div class="teamops-grid teamops-grid-compact">
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Visible Tasks', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( $metrics['total'] ); ?></p>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Blocked', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( $metrics['blocked'] ); ?></p>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Due Soon', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( $metrics['due_soon'] ); ?></p>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Overdue', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( $metrics['overdue'] ); ?></p>
				</div>
			</div>
			<div class="teamops-layout">
				<div class="teamops-panel teamops-panel-main">
					<div class="teamops-card">
						<h2><?php esc_html_e( 'Task List', 'teamops-hub' ); ?></h2>
						<?php $this->render_filters( $options, $filters ); ?>
						<?php $this->render_task_table( $tasks, $project_map, $user_map, $milestone_map, $status_map ); ?>
					</div>
					<?php if ( $task ) : ?>
						<div class="teamops-card">
							<h2><?php esc_html_e( 'Task Overview', 'teamops-hub' ); ?></h2>
							<div class="teamops-grid teamops-grid-compact">
								<div>
									<strong><?php esc_html_e( 'Status', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( $task['status_label'] ); ?></p>
								</div>
								<div>
									<strong><?php esc_html_e( 'Checklist Progress', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( sprintf( __( '%1$d%% (%2$d/%3$d)', 'teamops-hub' ), (int) $checklist['percentage'], (int) $checklist['completed'], (int) $checklist['total'] ) ); ?></p>
								</div>
								<div>
									<strong><?php esc_html_e( 'Milestone', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( ! empty( $task['milestone_id'] ) ? ( $milestone_map[ (int) $task['milestone_id'] ] ?? __( 'Unknown milestone', 'teamops-hub' ) ) : __( 'None', 'teamops-hub' ) ); ?></p>
								</div>
								<div>
									<strong><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( $task['due_date'] ?: '-' ); ?></p>
								</div>
							</div>
							<?php if ( ! empty( $task['description'] ) ) : ?>
								<div class="teamops-richtext"><?php echo wp_kses_post( wpautop( $task['description'] ) ); ?></div>
							<?php endif; ?>
						</div>
						<div class="teamops-card">
							<h2><?php esc_html_e( 'Checklist', 'teamops-hub' ); ?></h2>
							<?php $this->render_subtask_list( $task, $subtasks ); ?>
							<?php if ( $this->permissions->can_manage_tasks() ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-form-card teamops-mt-16">
									<?php wp_nonce_field( 'teamops_hub_save_subtask' ); ?>
									<input type="hidden" name="action" value="teamops_hub_save_subtask" />
									<input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" />
									<p><label for="teamops-subtask-title"><?php esc_html_e( 'Checklist Item', 'teamops-hub' ); ?></label><input id="teamops-subtask-title" type="text" class="regular-text teamops-input-full" name="title" required /></p>
									<p><label for="teamops-subtask-order"><?php esc_html_e( 'Sort Order', 'teamops-hub' ); ?></label><input id="teamops-subtask-order" type="number" class="teamops-input-full" name="sort_order" value="<?php echo esc_attr( count( $subtasks ) + 1 ); ?>" min="0" step="1" /></p>
									<?php submit_button( __( 'Add Checklist Item', 'teamops-hub' ) ); ?>
								</form>
							<?php endif; ?>
						</div>
						<div class="teamops-card">
							<h2><?php esc_html_e( 'Discussion', 'teamops-hub' ); ?></h2>
							<?php $this->render_comments_section( $task, $comments, $mention_hint ); ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="teamops-panel teamops-panel-side">
					<div class="teamops-card teamops-form-card">
						<h2><?php echo esc_html( $task ? __( 'Edit Task', 'teamops-hub' ) : __( 'Add Task', 'teamops-hub' ) ); ?></h2>
						<?php if ( $this->permissions->can_manage_tasks() ) : ?>
							<?php $this->render_form( $options, $task ); ?>
						<?php else : ?>
							<p><?php esc_html_e( 'Managers and administrators can create and edit tasks. Team members can update the status of tasks assigned to them from My Work.', 'teamops-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles save.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'teamops_manage_tasks' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage tasks.', 'teamops-hub' ) );
		}

		check_admin_referer( 'teamops_hub_save_task' );

		$task_id = absint( $_POST['task_id'] ?? 0 );
		$saved   = $this->tasks->save_task( wp_unslash( $_POST ), $task_id ?: null );

		if ( is_wp_error( $saved ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'          => 'teamops-hub-tasks',
						'task_id'       => $task_id,
						'teamops_error' => implode( ' ', $saved->get_error_data() ?: array( $saved->get_error_message() ) ),
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'teamops-hub-tasks',
					'task_id'        => $saved,
					'teamops_notice' => 'task_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Saves a checklist item.
	 *
	 * @return void
	 */
	public function handle_save_subtask() {
		if ( ! current_user_can( 'teamops_manage_tasks' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage checklist items.', 'teamops-hub' ) );
		}

		check_admin_referer( 'teamops_hub_save_subtask' );

		$task_id = absint( $_POST['task_id'] ?? 0 );
		$saved   = $this->subtasks->save_subtask( wp_unslash( $_POST ) );

		if ( is_wp_error( $saved ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'          => 'teamops-hub-tasks',
						'task_id'       => $task_id,
						'teamops_error' => implode( ' ', $saved->get_error_data() ?: array( $saved->get_error_message() ) ),
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'teamops-hub-tasks',
					'task_id'        => $task_id,
					'teamops_notice' => 'subtask_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Toggles a checklist item.
	 *
	 * @return void
	 */
	public function handle_toggle_subtask() {
		check_admin_referer( 'teamops_hub_toggle_subtask' );

		$subtask_id   = absint( $_POST['subtask_id'] ?? 0 );
		$is_completed = ! empty( $_POST['is_completed'] );
		$updated      = $this->subtasks->toggle_subtask( $subtask_id, $is_completed );
		$task_id      = absint( $_POST['task_id'] ?? 0 );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'teamops-hub-tasks',
					'task_id'        => $task_id,
					'teamops_notice' => $updated ? 'subtask_updated' : '',
					'teamops_error'  => $updated ? '' : __( 'Checklist item could not be updated.', 'teamops-hub' ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Saves a task comment and redirects back to the source page.
	 *
	 * @return void
	 */
	public function handle_save_comment() {
		check_admin_referer( 'teamops_hub_save_task_comment' );

		$task_id       = absint( $_POST['task_id'] ?? 0 );
		$redirect_page = sanitize_key( $_POST['redirect_page'] ?? 'teamops-hub-tasks' );
		$redirect_page = in_array( $redirect_page, array( 'teamops-hub-tasks', 'teamops-hub-my-work' ), true ) ? $redirect_page : 'teamops-hub-tasks';
		$saved         = $this->comments->save_comment( wp_unslash( $_POST ) );

		$args = array(
			'page' => $redirect_page,
		);

		if ( 'teamops-hub-tasks' === $redirect_page ) {
			$args['task_id'] = $task_id;
		}

		if ( is_wp_error( $saved ) ) {
			$args['teamops_error'] = implode( ' ', $saved->get_error_data() ?: array( $saved->get_error_message() ) );
		} else {
			$args['comment_saved'] = 1;
		}

		wp_safe_redirect(
			add_query_arg(
				$args,
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders filters.
	 *
	 * @param array $options Form options.
	 * @param array $filters Active filters.
	 * @return void
	 */
	private function render_filters( array $options, array $filters ) {
		?>
		<form method="get" class="teamops-filters">
			<input type="hidden" name="page" value="teamops-hub-tasks" />
			<input type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search tasks', 'teamops-hub' ); ?>" />
			<select name="filter_project_id">
				<option value="0"><?php esc_html_e( 'All Projects', 'teamops-hub' ); ?></option>
				<?php foreach ( $options['projects'] as $project ) : ?>
					<option value="<?php echo esc_attr( $project['id'] ); ?>" <?php selected( (int) $filters['project_id'], (int) $project['id'] ); ?>><?php echo esc_html( $project['title'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="filter_milestone_id">
				<option value="0"><?php esc_html_e( 'All Milestones', 'teamops-hub' ); ?></option>
				<?php foreach ( $options['milestones'] as $milestone ) : ?>
					<option value="<?php echo esc_attr( $milestone['id'] ); ?>" <?php selected( (int) $filters['milestone_id'], (int) $milestone['id'] ); ?>><?php echo esc_html( $milestone['title'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="filter_status">
				<option value=""><?php esc_html_e( 'All Statuses', 'teamops-hub' ); ?></option>
				<?php foreach ( $options['statuses'] as $status_value => $label ) : ?>
					<option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $filters['status'], $status_value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="filter_priority">
				<option value=""><?php esc_html_e( 'All Priorities', 'teamops-hub' ); ?></option>
				<?php foreach ( $options['priorities'] as $priority_value => $label ) : ?>
					<option value="<?php echo esc_attr( $priority_value ); ?>" <?php selected( $filters['priority'], $priority_value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="filter_assigned_user_id">
				<option value="0"><?php esc_html_e( 'All Assignees', 'teamops-hub' ); ?></option>
				<?php foreach ( $options['users'] as $user ) : ?>
					<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $filters['assigned_user_id'], (int) $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="filter_due_bucket">
				<option value=""><?php esc_html_e( 'Any Due Date', 'teamops-hub' ); ?></option>
				<option value="overdue" <?php selected( $filters['due_bucket'], 'overdue' ); ?>><?php esc_html_e( 'Overdue', 'teamops-hub' ); ?></option>
				<option value="due_soon" <?php selected( $filters['due_bucket'], 'due_soon' ); ?>><?php esc_html_e( 'Due Soon', 'teamops-hub' ); ?></option>
				<option value="no_due_date" <?php selected( $filters['due_bucket'], 'no_due_date' ); ?>><?php esc_html_e( 'No Due Date', 'teamops-hub' ); ?></option>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Apply', 'teamops-hub' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Renders task list.
	 *
	 * @param array $tasks Task rows.
	 * @param array $project_map Project lookup.
	 * @param array $user_map User lookup.
	 * @param array $milestone_map Milestone lookup.
	 * @param array $status_map Status lookup.
	 * @return void
	 */
	private function render_task_table( array $tasks, array $project_map, array $user_map, array $milestone_map, array $status_map ) {
		if ( empty( $tasks ) ) {
			echo '<p>' . esc_html__( 'No tasks found.', 'teamops-hub' ) . '</p>';
			return;
		}

		echo '<div class="teamops-table-wrap">';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'Task', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Project', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Milestone', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Checklist', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Assignee', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Status', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Priority', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Due', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Action', 'teamops-hub' ) . '</th></tr></thead><tbody>';

		foreach ( $tasks as $task ) {
			$link = add_query_arg(
				array(
					'page'    => 'teamops-hub-tasks',
					'task_id' => $task['id'],
				),
				admin_url( 'admin.php' )
			);

			$checklist = $task['checklist_summary'] ?? array( 'completed' => 0, 'total' => 0 );

			echo '<tr>';
			echo '<td>' . esc_html( $task['title'] ) . '</td>';
			echo '<td>' . esc_html( $project_map[ (int) $task['project_id'] ] ?? '#' . $task['project_id'] ) . '</td>';
			echo '<td>' . esc_html( ! empty( $task['milestone_id'] ) ? ( $milestone_map[ (int) $task['milestone_id'] ] ?? '#' . $task['milestone_id'] ) : '-' ) . '</td>';
			echo '<td>' . esc_html( sprintf( '%1$d/%2$d', (int) $checklist['completed'], (int) $checklist['total'] ) ) . '</td>';
			echo '<td>' . esc_html( $user_map[ (int) $task['assigned_user_id'] ] ?? __( 'Unassigned', 'teamops-hub' ) ) . '</td>';
			echo '<td>' . esc_html( $status_map[ $task['status'] ] ?? $task['status_label'] ?? ucwords( str_replace( '_', ' ', $task['status'] ) ) ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $task['priority'] ) ) . '</td>';
			echo '<td>' . esc_html( $task['due_date'] ?: '-' ) . '</td>';
			echo '<td><a class="button button-small" href="' . esc_url( $link ) . '">' . esc_html__( 'Open', 'teamops-hub' ) . '</a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Renders task form.
	 *
	 * @param array      $options Form options.
	 * @param array|null $task Task data.
	 * @return void
	 */
	private function render_form( array $options, $task = null ) {
		$task = $task ?: array(
			'id'               => 0,
			'project_id'       => 0,
			'milestone_id'     => 0,
			'title'            => '',
			'description'      => '',
			'status'           => array_key_first( $options['statuses'] ) ?: 'todo',
			'priority'         => 'medium',
			'assigned_user_id' => get_current_user_id(),
			'start_date'       => '',
			'due_date'         => '',
			'estimated_hours'  => '',
			'actual_hours'     => '',
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-task-form">
			<?php wp_nonce_field( 'teamops_hub_save_task' ); ?>
			<input type="hidden" name="action" value="teamops_hub_save_task" />
			<input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" />
			<p><label for="teamops-task-project"><?php esc_html_e( 'Project', 'teamops-hub' ); ?></label><select id="teamops-task-project" class="teamops-input-full" name="project_id" required><?php foreach ( $options['projects'] as $project ) : ?><option value="<?php echo esc_attr( $project['id'] ); ?>" data-member-ids="<?php echo esc_attr( implode( ',', array_map( 'intval', $project['member_ids'] ?? array() ) ) ); ?>" <?php selected( (int) $task['project_id'], (int) $project['id'] ); ?>><?php echo esc_html( $project['title'] ); ?></option><?php endforeach; ?></select></p>
			<p><label for="teamops-task-milestone"><?php esc_html_e( 'Milestone', 'teamops-hub' ); ?></label><select id="teamops-task-milestone" class="teamops-input-full" name="milestone_id"><option value="0"><?php esc_html_e( 'No Milestone', 'teamops-hub' ); ?></option><?php foreach ( $options['milestones'] as $milestone ) : ?><option value="<?php echo esc_attr( $milestone['id'] ); ?>" data-project-id="<?php echo esc_attr( $milestone['project_id'] ); ?>" <?php selected( (int) $task['milestone_id'], (int) $milestone['id'] ); ?>><?php echo esc_html( $milestone['title'] ); ?></option><?php endforeach; ?></select></p>
			<p><label for="teamops-task-title"><?php esc_html_e( 'Title', 'teamops-hub' ); ?></label><input id="teamops-task-title" type="text" class="regular-text teamops-input-full" name="title" value="<?php echo esc_attr( $task['title'] ); ?>" required /></p>
			<p><label for="teamops-task-description"><?php esc_html_e( 'Description', 'teamops-hub' ); ?></label><textarea id="teamops-task-description" class="large-text teamops-input-full" rows="5" name="description"><?php echo esc_textarea( $task['description'] ); ?></textarea></p>
			<p><label for="teamops-task-status"><?php esc_html_e( 'Status', 'teamops-hub' ); ?></label><?php $this->render_select( 'status', $options['statuses'], $task['status'], 'teamops-task-status' ); ?></p>
			<p><label for="teamops-task-priority"><?php esc_html_e( 'Priority', 'teamops-hub' ); ?></label><?php $this->render_select( 'priority', $options['priorities'], $task['priority'], 'teamops-task-priority' ); ?></p>
			<p><label for="teamops-task-assignee"><?php esc_html_e( 'Assigned To', 'teamops-hub' ); ?></label><select id="teamops-task-assignee" class="teamops-input-full" name="assigned_user_id" required><?php foreach ( $options['users'] as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $task['assigned_user_id'], (int) $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select><small><?php esc_html_e( 'Only project members and the project owner can be assigned.', 'teamops-hub' ); ?></small></p>
			<p><label for="teamops-task-start"><?php esc_html_e( 'Start Date', 'teamops-hub' ); ?></label><input id="teamops-task-start" type="date" class="teamops-input-full" name="start_date" value="<?php echo esc_attr( $task['start_date'] ); ?>" /></p>
			<p><label for="teamops-task-due"><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></label><input id="teamops-task-due" type="date" class="teamops-input-full" name="due_date" value="<?php echo esc_attr( $task['due_date'] ); ?>" /></p>
			<p><label for="teamops-task-estimated"><?php esc_html_e( 'Estimated Hours', 'teamops-hub' ); ?></label><input id="teamops-task-estimated" type="number" min="0" step="0.25" class="teamops-input-full" name="estimated_hours" value="<?php echo esc_attr( $task['estimated_hours'] ); ?>" /></p>
			<p><label for="teamops-task-actual"><?php esc_html_e( 'Actual Hours', 'teamops-hub' ); ?></label><input id="teamops-task-actual" type="number" min="0" step="0.25" class="teamops-input-full" name="actual_hours" value="<?php echo esc_attr( $task['actual_hours'] ); ?>" /></p>
			<?php submit_button( $task['id'] ? __( 'Update Task', 'teamops-hub' ) : __( 'Create Task', 'teamops-hub' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Renders checklist items.
	 *
	 * @param array $task Parent task.
	 * @param array $subtasks Subtask rows.
	 * @return void
	 */
	private function render_subtask_list( array $task, array $subtasks ) {
		if ( empty( $subtasks ) ) {
			echo '<p>' . esc_html__( 'No checklist items yet.', 'teamops-hub' ) . '</p>';
			return;
		}

		echo '<ul class="teamops-subtask-list">';

		foreach ( $subtasks as $subtask ) {
			echo '<li>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="teamops-subtask-toggle-form">';
			wp_nonce_field( 'teamops_hub_toggle_subtask' );
			echo '<input type="hidden" name="action" value="teamops_hub_toggle_subtask" />';
			echo '<input type="hidden" name="task_id" value="' . esc_attr( $task['id'] ) . '" />';
			echo '<input type="hidden" name="subtask_id" value="' . esc_attr( $subtask['id'] ) . '" />';
			echo '<input type="hidden" name="is_completed" value="' . esc_attr( empty( $subtask['is_completed'] ) ? 1 : 0 ) . '" />';
			echo '<button type="submit" class="button-link teamops-subtask-toggle">' . ( ! empty( $subtask['is_completed'] ) ? esc_html__( 'Completed', 'teamops-hub' ) : esc_html__( 'Mark Done', 'teamops-hub' ) ) . '</button>';
			echo '</form>';
			echo '<span class="' . ( ! empty( $subtask['is_completed'] ) ? 'teamops-subtask-complete' : '' ) . '">' . esc_html( $subtask['title'] ) . '</span>';
			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Renders discussion thread and comment form.
	 *
	 * @param array  $task Current task.
	 * @param array  $comments Comment rows.
	 * @param string $mention_hint Mention hint string.
	 * @return void
	 */
	private function render_comments_section( array $task, array $comments, $mention_hint ) {
		if ( empty( $comments ) ) {
			echo '<p>' . esc_html__( 'No comments yet.', 'teamops-hub' ) . '</p>';
		} else {
			echo '<ul class="teamops-comment-list">';

			foreach ( $comments as $comment ) {
				echo '<li>';
				echo '<div class="teamops-comment-meta"><strong>' . esc_html( $comment['display_name'] ?: $comment['user_login'] ) . '</strong><small>' . esc_html( $comment['created_at'] ) . '</small></div>';
				echo '<div class="teamops-comment-body">' . wp_kses_post( wpautop( esc_html( $comment['content'] ) ) ) . '</div>';
				echo '</li>';
			}

			echo '</ul>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-form-card teamops-mt-16">
			<?php wp_nonce_field( 'teamops_hub_save_task_comment' ); ?>
			<input type="hidden" name="action" value="teamops_hub_save_task_comment" />
			<input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" />
			<input type="hidden" name="redirect_page" value="teamops-hub-tasks" />
			<p><label for="teamops-task-comment"><?php esc_html_e( 'Add Comment', 'teamops-hub' ); ?></label><textarea id="teamops-task-comment" class="large-text teamops-input-full" rows="4" name="content" required></textarea></p>
			<?php if ( '' !== $mention_hint ) : ?>
				<p><small><?php echo esc_html( sprintf( __( 'Mention teammates with: %s', 'teamops-hub' ), $mention_hint ) ); ?></small></p>
			<?php endif; ?>
			<?php submit_button( __( 'Post Comment', 'teamops-hub' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Renders select field.
	 *
	 * @param string $name Field name.
	 * @param array  $options Options.
	 * @param string $selected_value Current value.
	 * @param string $id Field id.
	 * @return void
	 */
	private function render_select( $name, array $options, $selected_value, $id ) {
		echo '<select id="' . esc_attr( $id ) . '" class="teamops-input-full" name="' . esc_attr( $name ) . '">';

		foreach ( $options as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected_value, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Renders success and error notices.
	 *
	 * @return void
	 */
	private function render_notices() {
		$notice = sanitize_key( $_GET['teamops_notice'] ?? '' );
		$error  = sanitize_text_field( wp_unslash( $_GET['teamops_error'] ?? '' ) );

		if ( 'task_saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Task saved successfully.', 'teamops-hub' ) . '</p></div>';
		}

		if ( 'subtask_saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Checklist item saved successfully.', 'teamops-hub' ) . '</p></div>';
		}

		if ( 'subtask_updated' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Checklist item updated.', 'teamops-hub' ) . '</p></div>';
		}

		if ( ! empty( $_GET['comment_saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Comment added.', 'teamops-hub' ) . '</p></div>';
		}

		if ( '' !== $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}
	}

	/**
	 * Removes empty filter values.
	 *
	 * @param array $filters Raw filters.
	 * @return array
	 */
	private function active_filters( array $filters ) {
		return array_filter(
			$filters,
			static function ( $value ) {
				return '' !== $value && 0 !== $value;
			}
		);
	}

	/**
	 * Builds a user lookup array.
	 *
	 * @param array $users WP_User list.
	 * @return array
	 */
	private function user_map( array $users ) {
		$map = array();

		foreach ( $users as $user ) {
			$map[ (int) $user->ID ] = $user->display_name;
		}

		return $map;
	}

	/**
	 * Builds a project lookup array.
	 *
	 * @param array $projects Project rows.
	 * @return array
	 */
	private function project_map( array $projects ) {
		$map = array();

		foreach ( $projects as $project ) {
			$map[ (int) $project['id'] ] = $project['title'];
		}

		return $map;
	}

	/**
	 * Builds a milestone lookup array.
	 *
	 * @param array $milestones Milestone rows.
	 * @return array
	 */
	private function milestone_map( array $milestones ) {
		$map = array();

		foreach ( $milestones as $milestone ) {
			$map[ (int) $milestone['id'] ] = $milestone['title'];
		}

		return $map;
	}

	/**
	 * Calculates task metrics from visible rows.
	 *
	 * @param array $tasks Task rows.
	 * @return array
	 */
	private function task_metrics( array $tasks ) {
		$today    = current_time( 'Y-m-d' );
		$due_soon = gmdate( 'Y-m-d', strtotime( '+7 days', strtotime( $today ) ) );
		$metrics  = array(
			'total'    => count( $tasks ),
			'blocked'  => 0,
			'due_soon' => 0,
			'overdue'  => 0,
		);

		foreach ( $tasks as $task ) {
			if ( 'blocked' === $task['status'] ) {
				++$metrics['blocked'];
			}

			if ( ! empty( $task['due_date'] ) && $task['due_date'] < $today && empty( $task['completed_at'] ) ) {
				++$metrics['overdue'];
			}

			if ( ! empty( $task['due_date'] ) && $task['due_date'] >= $today && $task['due_date'] <= $due_soon && empty( $task['completed_at'] ) ) {
				++$metrics['due_soon'];
			}
		}

		return $metrics;
	}
}
