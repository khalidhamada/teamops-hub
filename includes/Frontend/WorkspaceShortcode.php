<?php
/**
 * Front-end workspace shortcode.
 *
 * @package TeamOpsHub\Frontend
 */

namespace TeamOpsHub\Frontend;

use TeamOpsHub\Services\NotificationService;
use TeamOpsHub\Services\ProjectService;
use TeamOpsHub\Services\SubtaskService;
use TeamOpsHub\Services\TaskCommentService;
use TeamOpsHub\Services\TaskService;

defined( 'ABSPATH' ) || exit;

class WorkspaceShortcode {
	private $projects;
	private $tasks;
	private $subtasks;
	private $comments;
	private $notifications;

	public function __construct( ProjectService $projects, TaskService $tasks, SubtaskService $subtasks, TaskCommentService $comments, NotificationService $notifications ) {
		$this->projects = $projects;
		$this->tasks = $tasks;
		$this->subtasks = $subtasks;
		$this->comments = $comments;
		$this->notifications = $notifications;

		add_shortcode( 'teamops_hub_workspace', array( $this, 'render' ) );
		add_action( 'admin_post_teamops_hub_front_update_task_status', array( $this, 'handle_status_update' ) );
		add_action( 'admin_post_teamops_hub_front_update_task', array( $this, 'handle_task_update' ) );
		add_action( 'admin_post_teamops_hub_front_toggle_subtask', array( $this, 'handle_subtask_toggle' ) );
		add_action( 'admin_post_teamops_hub_front_save_comment', array( $this, 'handle_save_comment' ) );
		add_action( 'admin_post_teamops_hub_front_mark_notifications_read', array( $this, 'handle_mark_notifications_read' ) );
		add_action( 'wp_ajax_teamops_hub_front_move_task', array( $this, 'handle_kanban_move' ) );
	}

	public function render() {
		if ( ! is_user_logged_in() ) {
			return sprintf( '<div class="teamops-front-shell"><p>%s <a href="%s">%s</a></p></div>', esc_html__( 'Please sign in to access your TeamOps Hub workspace.', 'teamops-hub' ), esc_url( wp_login_url( get_permalink() ) ), esc_html__( 'Log in', 'teamops-hub' ) );
		}

		if ( ! current_user_can( 'teamops_access' ) ) {
			return '<div class="teamops-front-shell"><p>' . esc_html__( 'You do not have access to TeamOps Hub.', 'teamops-hub' ) . '</p></div>';
		}

		wp_enqueue_style( 'teamops-hub-frontend', TEAMOPS_HUB_URL . 'assets/css/frontend.css', array(), $this->asset_version( 'assets/css/frontend.css' ) );
		wp_enqueue_script( 'teamops-hub-frontend', TEAMOPS_HUB_URL . 'assets/js/frontend.js', array(), $this->asset_version( 'assets/js/frontend.js' ), true );

		$selected_project_id = absint( $_GET['teamops_project'] ?? 0 );
		$active_tab = sanitize_key( $_GET['teamops_tab'] ?? ( $selected_project_id ? 'overview' : 'dashboard' ) );
		$status_filter = sanitize_key( $_GET['teamops_status'] ?? '' );
		$is_manager = current_user_can( 'teamops_manage_tasks' ) || current_user_can( 'manage_options' );
		$projects = $this->projects->get_projects();
		$project_lookup = $this->project_map( $projects );
		$options = $this->tasks->form_options();
		$status_rows = $this->tasks->status_definitions();
		$all_tasks = $this->tasks->get_tasks();
		$notifications = $this->notifications->recent_for_current_user( 8 );
		$unread_count = $this->notifications->unread_count_for_current_user();
		$selected_project = $selected_project_id ? $this->projects->get_project( $selected_project_id ) : null;

		if ( $selected_project_id && ! $selected_project ) {
			$selected_project_id = 0;
			$active_tab = 'dashboard';
			$status_filter = '';
		}

		$active_tab = $selected_project_id ? $this->normalize_tab( $active_tab ) : 'dashboard';
		$current_url = $this->workspace_url(
			array(
				'teamops_project' => $selected_project_id,
				'teamops_tab' => $active_tab,
				'teamops_status' => $status_filter,
			)
		);

		$selected_tasks = array();
		$selected_milestones = array();
		$kanban_columns = array();
		$selected_members = array();
		$project_stats = array();

		if ( $selected_project_id ) {
			$selected_tasks = $this->tasks->get_tasks( $this->active_filters( array( 'project_id' => $selected_project_id, 'status' => $status_filter ) ) );
			$selected_milestones = $this->milestones_for_project( $options['milestones'], $selected_project_id );
			$kanban_columns = $this->kanban_columns( $this->tasks->get_tasks( array( 'project_id' => $selected_project_id ) ), $status_rows );
			$selected_members = $this->project_members( $selected_project );
			$project_stats = $this->project_stats( $selected_project, $all_tasks, $options['milestones'] );
		}

		$portfolio_stats = $this->portfolio_stats( $projects, $all_tasks, $options['milestones'] );
		$project_cards = $this->project_cards( $projects, $all_tasks, $options['milestones'] );

		wp_localize_script(
			'teamops-hub-frontend',
			'teamopsHubWorkspace',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'kanbanNonce' => wp_create_nonce( 'teamops_hub_front_move_task' ),
				'kanbanSuccess' => __( 'Task moved on the board.', 'teamops-hub' ),
				'kanbanError' => __( 'The task could not be moved.', 'teamops-hub' ),
			)
		);

		ob_start();
		?>
		<div class="teamops-front-shell">
			<?php echo $this->render_notices(); ?>
			<?php if ( ! $selected_project_id ) : ?>
				<?php echo $this->render_dashboard_view( $portfolio_stats, $project_cards, $notifications, $unread_count ); ?>
			<?php else : ?>
				<?php echo $this->render_project_view( $selected_project, $project_stats, $selected_members, $selected_milestones, $selected_tasks, $kanban_columns, $options, $project_lookup, $current_url, $active_tab, $status_filter, $is_manager ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_dashboard_view( array $portfolio_stats, array $project_cards, array $notifications, $unread_count ) {
		ob_start();
		?>
		<section class="teamops-front-hero teamops-front-hero-dashboard">
			<div>
				<p class="teamops-front-kicker"><?php esc_html_e( 'Portfolio Dashboard', 'teamops-hub' ); ?></p>
				<h2><?php esc_html_e( 'See every project before diving into the work.', 'teamops-hub' ); ?></h2>
				<p><?php esc_html_e( 'Start with the whole portfolio, then open a project workspace for execution, planning, and collaboration.', 'teamops-hub' ); ?></p>
			</div>
			<div class="teamops-front-metrics teamops-front-metrics-quad">
				<div><span><?php esc_html_e( 'Projects', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $portfolio_stats['projects'] ); ?></strong></div>
				<div><span><?php esc_html_e( 'Active', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $portfolio_stats['active_projects'] ); ?></strong></div>
				<div><span><?php esc_html_e( 'Overdue Tasks', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $portfolio_stats['overdue_tasks'] ); ?></strong></div>
				<div><span><?php esc_html_e( 'Unread', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $unread_count ); ?></strong></div>
			</div>
		</section>
		<div class="teamops-front-dashboard-grid">
			<section class="teamops-front-panel">
				<div class="teamops-front-panel-head"><div><h3><?php esc_html_e( 'Project Portfolio', 'teamops-hub' ); ?></h3><p><?php esc_html_e( 'Open any project to switch into its focused workspace.', 'teamops-hub' ); ?></p></div></div>
				<div class="teamops-front-project-grid">
					<?php foreach ( $project_cards as $project_card ) : ?>
						<a class="teamops-front-project-card" href="<?php echo esc_url( $project_card['url'] ); ?>">
							<div class="teamops-front-project-card-head"><div><p class="teamops-front-kicker"><?php echo esc_html( $project_card['status_label'] ); ?></p><h4><?php echo esc_html( $project_card['title'] ); ?></h4></div><span class="teamops-front-project-priority"><?php echo esc_html( $project_card['priority_label'] ); ?></span></div>
							<div class="teamops-front-project-card-meta"><span><?php echo esc_html( sprintf( __( '%d tasks', 'teamops-hub' ), $project_card['total_tasks'] ) ); ?></span><span><?php echo esc_html( sprintf( __( '%d open', 'teamops-hub' ), $project_card['open_tasks'] ) ); ?></span><span><?php echo esc_html( sprintf( __( '%d overdue', 'teamops-hub' ), $project_card['overdue_tasks'] ) ); ?></span></div>
							<div class="teamops-front-progress teamops-front-progress-project" aria-hidden="true"><span style="width: <?php echo esc_attr( max( 6, (int) $project_card['progress'] ) ); ?>%;"></span></div>
							<p class="teamops-front-project-card-note"><?php echo esc_html( sprintf( __( '%1$d milestones, %2$d completed tasks', 'teamops-hub' ), $project_card['milestone_total'], $project_card['completed_tasks'] ) ); ?></p>
							<span class="teamops-front-project-link"><?php esc_html_e( 'Open project workspace', 'teamops-hub' ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<aside class="teamops-front-stack">
				<section class="teamops-front-panel">
					<div class="teamops-front-panel-head"><div><h3><?php esc_html_e( 'Notifications', 'teamops-hub' ); ?></h3><p><?php echo esc_html( sprintf( __( '%d unread', 'teamops-hub' ), (int) $unread_count ) ); ?></p></div></div>
					<?php if ( empty( $notifications ) ) : ?>
						<p><?php esc_html_e( 'No notifications yet.', 'teamops-hub' ); ?></p>
					<?php else : ?>
						<ul class="teamops-front-notification-list">
							<?php foreach ( $notifications as $notification ) : ?>
								<li class="<?php echo ! empty( $notification['is_read'] ) ? 'is-read' : 'is-unread'; ?>"><strong><?php echo esc_html( $notification['title'] ); ?></strong><p><?php echo esc_html( $notification['body'] ); ?></p></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</section>
				<section class="teamops-front-panel">
					<h3><?php esc_html_e( 'Portfolio Snapshot', 'teamops-hub' ); ?></h3>
					<ul class="teamops-front-overview-list">
						<li><span><?php esc_html_e( 'Total tasks', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $portfolio_stats['tasks'] ); ?></strong></li>
						<li><span><?php esc_html_e( 'Completed tasks', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $portfolio_stats['completed_tasks'] ); ?></strong></li>
						<li><span><?php esc_html_e( 'Milestones', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $portfolio_stats['milestones'] ); ?></strong></li>
					</ul>
				</section>
			</aside>
		</div>
		<?php
		return ob_get_clean();
	}
	private function render_project_view( array $project, array $project_stats, array $members, array $milestones, array $tasks, array $kanban_columns, array $options, array $project_lookup, $current_url, $active_tab, $status_filter, $is_manager ) {
		ob_start();
		?>
		<section class="teamops-front-project-header teamops-front-panel">
			<a class="teamops-front-backlink" href="<?php echo esc_url( $this->workspace_url() ); ?>"><?php esc_html_e( 'Back to all projects', 'teamops-hub' ); ?></a>
			<div class="teamops-front-project-heading">
				<div>
					<p class="teamops-front-kicker"><?php esc_html_e( 'Project Workspace', 'teamops-hub' ); ?></p>
					<h2><?php echo esc_html( $project['title'] ); ?></h2>
					<?php if ( ! empty( $project['description'] ) ) : ?><p class="teamops-front-project-summary"><?php echo esc_html( wp_strip_all_tags( $project['description'] ) ); ?></p><?php endif; ?>
				</div>
				<div class="teamops-front-project-statusline"><span><?php echo esc_html( ucfirst( str_replace( '_', ' ', $project['status'] ) ) ); ?></span><span><?php echo esc_html( ucfirst( $project['priority'] ) ); ?></span></div>
			</div>
			<div class="teamops-front-metrics teamops-front-project-metrics">
				<div><span><?php esc_html_e( 'Progress', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $project_stats['progress'] ); ?>%</strong></div>
				<div><span><?php esc_html_e( 'Tasks', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $project_stats['total_tasks'] ); ?></strong></div>
				<div><span><?php esc_html_e( 'Overdue', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $project_stats['overdue_tasks'] ); ?></strong></div>
				<div><span><?php esc_html_e( 'Milestones', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $project_stats['milestone_total'] ); ?></strong></div>
			</div>
		</section>

		<nav class="teamops-front-tabs" aria-label="<?php esc_attr_e( 'Project workspace tabs', 'teamops-hub' ); ?>">
			<?php foreach ( $this->project_tabs() as $tab_key => $tab_label ) : ?>
				<a class="teamops-front-tab <?php echo $active_tab === $tab_key ? 'is-active' : ''; ?>" href="<?php echo esc_url( $this->workspace_url( array( 'teamops_project' => (int) $project['id'], 'teamops_tab' => $tab_key, 'teamops_status' => 'tasks' === $tab_key ? $status_filter : '' ) ) ); ?>"><?php echo esc_html( $tab_label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( 'overview' === $active_tab ) : ?>
			<div class="teamops-front-overview-grid">
				<section class="teamops-front-panel">
					<h3><?php esc_html_e( 'Project Details', 'teamops-hub' ); ?></h3>
					<ul class="teamops-front-overview-list">
						<li><span><?php esc_html_e( 'Status', 'teamops-hub' ); ?></span><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $project['status'] ) ) ); ?></strong></li>
						<li><span><?php esc_html_e( 'Priority', 'teamops-hub' ); ?></span><strong><?php echo esc_html( ucfirst( $project['priority'] ) ); ?></strong></li>
						<li><span><?php esc_html_e( 'Start Date', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $project['start_date'] ?: __( 'Not set', 'teamops-hub' ) ); ?></strong></li>
						<li><span><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></span><strong><?php echo esc_html( $project['due_date'] ?: __( 'Not set', 'teamops-hub' ) ); ?></strong></li>
						<li><span><?php esc_html_e( 'Team Members', 'teamops-hub' ); ?></span><strong><?php echo esc_html( count( $members ) ); ?></strong></li>
					</ul>
				</section>
				<section class="teamops-front-panel">
					<h3><?php esc_html_e( 'Team', 'teamops-hub' ); ?></h3>
					<?php if ( empty( $members ) ) : ?><p><?php esc_html_e( 'No team members are assigned yet.', 'teamops-hub' ); ?></p><?php else : ?><ul class="teamops-front-people-list"><?php foreach ( $members as $member ) : ?><li><?php echo esc_html( $member->display_name ); ?></li><?php endforeach; ?></ul><?php endif; ?>
				</section>
				<section class="teamops-front-panel">
					<h3><?php esc_html_e( 'Milestone Snapshot', 'teamops-hub' ); ?></h3>
					<?php if ( empty( $milestones ) ) : ?><p><?php esc_html_e( 'No milestones yet.', 'teamops-hub' ); ?></p><?php else : ?><ul class="teamops-front-overview-list"><?php foreach ( array_slice( $milestones, 0, 4 ) as $milestone ) : ?><li><span><?php echo esc_html( $milestone['title'] ); ?></span><strong><?php echo esc_html( ucfirst( str_replace( '_', ' ', $milestone['status'] ) ) ); ?></strong></li><?php endforeach; ?></ul><?php endif; ?>
				</section>
				<section class="teamops-front-panel">
					<h3><?php esc_html_e( 'Recent Work', 'teamops-hub' ); ?></h3>
					<?php if ( empty( $tasks ) ) : ?><p><?php esc_html_e( 'No tasks for this project yet.', 'teamops-hub' ); ?></p><?php else : ?><ul class="teamops-front-overview-list"><?php foreach ( array_slice( $tasks, 0, 4 ) as $task ) : ?><li><span><?php echo esc_html( $task['title'] ); ?></span><strong><?php echo esc_html( $task['status_label'] ); ?></strong></li><?php endforeach; ?></ul><?php endif; ?>
				</section>
			</div>
		<?php elseif ( 'kanban' === $active_tab ) : ?>
			<section class="teamops-front-panel teamops-front-kanban-panel">
				<div class="teamops-front-panel-head"><div><h3><?php esc_html_e( 'Kanban Board', 'teamops-hub' ); ?></h3><p><?php esc_html_e( 'Move tasks between columns to keep the project flowing.', 'teamops-hub' ); ?></p></div></div>
				<div class="teamops-front-kanban" data-teamops-kanban>
					<?php foreach ( $kanban_columns as $column ) : ?>
						<section class="teamops-front-kanban-column" data-status-key="<?php echo esc_attr( $column['key'] ); ?>" style="<?php echo esc_attr( '--teamops-status-accent:' . $this->status_color( $column['color'] ) . ';' ); ?>">
							<header class="teamops-front-kanban-head"><div><h4><?php echo esc_html( $column['label'] ); ?></h4><span><?php echo esc_html( sprintf( _n( '%d task', '%d tasks', count( $column['tasks'] ), 'teamops-hub' ), count( $column['tasks'] ) ) ); ?></span></div></header>
							<div class="teamops-front-kanban-dropzone" data-status-key="<?php echo esc_attr( $column['key'] ); ?>">
								<?php if ( empty( $column['tasks'] ) ) : ?>
									<p class="teamops-front-kanban-empty"><?php esc_html_e( 'Drop tasks here.', 'teamops-hub' ); ?></p>
								<?php else : ?>
									<?php foreach ( $column['tasks'] as $task ) : ?>
										<?php $task_checklist = $task['checklist_summary']; ?>
										<a class="teamops-front-kanban-card" href="<?php echo esc_url( $this->workspace_url( array( 'teamops_project' => (int) $project['id'], 'teamops_tab' => 'tasks' ) ) ); ?>#teamops-task-<?php echo esc_attr( $task['id'] ); ?>" draggable="true" data-task-id="<?php echo esc_attr( $task['id'] ); ?>" data-status-key="<?php echo esc_attr( $column['key'] ); ?>"><strong><?php echo esc_html( $task['title'] ); ?></strong><span><?php echo esc_html( $project_lookup[ (int) $task['project_id'] ] ?? __( 'Project', 'teamops-hub' ) ); ?></span><div class="teamops-front-kanban-meta"><?php if ( ! empty( $task['due_date'] ) ) : ?><span><?php echo esc_html( $task['due_date'] ); ?></span><?php endif; ?><span><?php echo esc_html( sprintf( __( '%1$d/%2$d checklist', 'teamops-hub' ), (int) $task_checklist['completed'], (int) $task_checklist['total'] ) ); ?></span></div></a>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			</section>
		<?php elseif ( 'tasks' === $active_tab ) : ?>
			<section class="teamops-front-panel teamops-front-toolbar">
				<div class="teamops-front-panel-head"><div><h3><?php esc_html_e( 'Tasks', 'teamops-hub' ); ?></h3><p><?php esc_html_e( 'Filter and update detailed task records for this project.', 'teamops-hub' ); ?></p></div></div>
				<form method="get" action="<?php echo esc_url( $this->current_url() ); ?>" class="teamops-front-filters">
					<input type="hidden" name="teamops_project" value="<?php echo esc_attr( $project['id'] ); ?>" />
					<input type="hidden" name="teamops_tab" value="tasks" />
					<select name="teamops_status"><option value=""><?php esc_html_e( 'All Statuses', 'teamops-hub' ); ?></option><?php foreach ( $options['statuses'] as $status_value => $label ) : ?><option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $status_filter, $status_value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
					<button type="submit" class="teamops-front-button"><?php esc_html_e( 'Apply Filter', 'teamops-hub' ); ?></button>
				</form>
			</section>
			<?php if ( empty( $tasks ) ) : ?>
				<section class="teamops-front-panel"><p><?php esc_html_e( 'No tasks match the current view.', 'teamops-hub' ); ?></p></section>
			<?php else : ?>
				<div class="teamops-front-task-stack"><?php foreach ( $tasks as $task ) : ?><?php echo $this->render_task_card( $task, $options, $project_lookup, $current_url, $is_manager ); ?><?php endforeach; ?></div>
			<?php endif; ?>
		<?php elseif ( 'milestones' === $active_tab ) : ?>
			<section class="teamops-front-panel">
				<div class="teamops-front-panel-head"><div><h3><?php esc_html_e( 'Milestones', 'teamops-hub' ); ?></h3><p><?php esc_html_e( 'Track major checkpoints for this project.', 'teamops-hub' ); ?></p></div></div>
				<?php if ( empty( $milestones ) ) : ?><p><?php esc_html_e( 'No milestones are attached to this project yet.', 'teamops-hub' ); ?></p><?php else : ?><div class="teamops-front-milestone-grid"><?php foreach ( $milestones as $milestone ) : ?><article class="teamops-front-milestone-card"><p class="teamops-front-kicker"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $milestone['status'] ) ) ); ?></p><h4><?php echo esc_html( $milestone['title'] ); ?></h4><?php if ( ! empty( $milestone['description'] ) ) : ?><p><?php echo esc_html( $milestone['description'] ); ?></p><?php endif; ?><p class="teamops-front-milestone-date"><?php echo esc_html( $milestone['due_date'] ?: __( 'No due date', 'teamops-hub' ) ); ?></p></article><?php endforeach; ?></div><?php endif; ?>
			</section>
		<?php else : ?>
			<section class="teamops-front-panel teamops-front-empty-tab"><h3><?php esc_html_e( 'Files', 'teamops-hub' ); ?></h3><p><?php esc_html_e( 'Attachments will live here once the file workflow is added. This tab is reserved so the project workspace stays organized as more features arrive.', 'teamops-hub' ); ?></p></section>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
	private function render_task_card( array $task, array $options, array $project_lookup, $current_url, $is_manager ) {
		$subtasks = $this->subtasks->get_subtasks( (int) $task['id'] );
		$comments = $this->comments->get_comments( (int) $task['id'] );
		$mention_hint = $this->comments->mention_hint( (int) $task['id'] );
		$checklist = $task['checklist_summary'];
		$task_milestones = $this->milestones_for_project( $options['milestones'], (int) $task['project_id'] );
		$task_users = $this->users_for_project( $options, (int) $task['project_id'], (int) $task['assigned_user_id'] );
		ob_start();
		?>
		<section class="teamops-front-task" id="teamops-task-<?php echo esc_attr( $task['id'] ); ?>">
			<div class="teamops-front-task-head"><div><p class="teamops-front-task-project"><?php echo esc_html( $project_lookup[ (int) $task['project_id'] ] ?? __( 'Project', 'teamops-hub' ) ); ?></p><h3><?php echo esc_html( $task['title'] ); ?></h3></div><div class="teamops-front-task-badge"><?php echo esc_html( sprintf( __( '%1$d/%2$d checklist', 'teamops-hub' ), (int) $checklist['completed'], (int) $checklist['total'] ) ); ?></div></div>
			<div class="teamops-front-task-meta"><span><?php echo esc_html( $task['status_label'] ); ?></span><?php if ( ! empty( $task['due_date'] ) ) : ?><span><?php echo esc_html( $task['due_date'] ); ?></span><?php endif; ?><span><?php echo esc_html( sprintf( _n( '%d comment', '%d comments', count( $comments ), 'teamops-hub' ), count( $comments ) ) ); ?></span></div>
			<div class="teamops-front-progress" aria-hidden="true"><span style="width: <?php echo esc_attr( max( 8, (int) $checklist['percentage'] ) ); ?>%;"></span></div>
			<?php if ( ! empty( $task['description'] ) ) : ?><div class="teamops-front-copy"><?php echo wp_kses_post( wpautop( $task['description'] ) ); ?></div><?php endif; ?>
			<div class="teamops-front-inline-form"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'teamops_hub_front_update_task_status' ); ?><input type="hidden" name="action" value="teamops_hub_front_update_task_status" /><input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" /><input type="hidden" name="redirect_url" value="<?php echo esc_url( $current_url ); ?>" /><select name="status"><?php foreach ( $options['statuses'] as $status_value => $label ) : ?><option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $task['status'], $status_value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button type="submit" class="teamops-front-button"><?php esc_html_e( 'Save Status', 'teamops-hub' ); ?></button></form></div>
			<?php if ( ! empty( $subtasks ) ) : ?><ul class="teamops-front-subtasks"><?php foreach ( $subtasks as $subtask ) : ?><li><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-front-checklist-form"><?php wp_nonce_field( 'teamops_hub_front_toggle_subtask' ); ?><input type="hidden" name="action" value="teamops_hub_front_toggle_subtask" /><input type="hidden" name="subtask_id" value="<?php echo esc_attr( $subtask['id'] ); ?>" /><input type="hidden" name="is_completed" value="<?php echo esc_attr( empty( $subtask['is_completed'] ) ? 1 : 0 ); ?>" /><input type="hidden" name="redirect_url" value="<?php echo esc_url( $current_url ); ?>" /><button type="submit" class="teamops-front-check-toggle <?php echo ! empty( $subtask['is_completed'] ) ? 'is-complete' : ''; ?>" aria-label="<?php echo esc_attr( ! empty( $subtask['is_completed'] ) ? __( 'Mark checklist item as open', 'teamops-hub' ) : __( 'Mark checklist item as completed', 'teamops-hub' ) ); ?>"><span class="teamops-front-check-box" aria-hidden="true"></span><span class="teamops-front-check-text <?php echo ! empty( $subtask['is_completed'] ) ? 'is-complete' : ''; ?>"><?php echo esc_html( $subtask['title'] ); ?></span></button></form></li><?php endforeach; ?></ul><?php endif; ?>
			<details class="teamops-front-task-editor"><summary><?php esc_html_e( 'Edit Task Details', 'teamops-hub' ); ?></summary><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-front-edit-form"><?php wp_nonce_field( 'teamops_hub_front_update_task' ); ?><input type="hidden" name="action" value="teamops_hub_front_update_task" /><input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" /><input type="hidden" name="redirect_url" value="<?php echo esc_url( $current_url ); ?>" /><div class="teamops-front-field-grid"><label><span><?php esc_html_e( 'Title', 'teamops-hub' ); ?></span><input type="text" name="title" value="<?php echo esc_attr( $task['title'] ); ?>" required /></label><label><span><?php esc_html_e( 'Priority', 'teamops-hub' ); ?></span><select name="priority"><?php foreach ( $options['priorities'] as $priority_value => $priority_label ) : ?><option value="<?php echo esc_attr( $priority_value ); ?>" <?php selected( $task['priority'], $priority_value ); ?>><?php echo esc_html( $priority_label ); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e( 'Start Date', 'teamops-hub' ); ?></span><input type="date" name="start_date" value="<?php echo esc_attr( $task['start_date'] ); ?>" /></label><label><span><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></span><input type="date" name="due_date" value="<?php echo esc_attr( $task['due_date'] ); ?>" /></label><label><span><?php esc_html_e( 'Actual Hours', 'teamops-hub' ); ?></span><input type="number" step="0.25" min="0" name="actual_hours" value="<?php echo esc_attr( $task['actual_hours'] ); ?>" /></label><label><span><?php esc_html_e( 'Status', 'teamops-hub' ); ?></span><select name="status"><?php foreach ( $options['statuses'] as $status_value => $label ) : ?><option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $task['status'], $status_value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><?php if ( $is_manager ) : ?><label><span><?php esc_html_e( 'Assigned To', 'teamops-hub' ); ?></span><select name="assigned_user_id"><?php foreach ( $task_users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $task['assigned_user_id'], (int) $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e( 'Milestone', 'teamops-hub' ); ?></span><select name="milestone_id"><option value="0"><?php esc_html_e( 'No Milestone', 'teamops-hub' ); ?></option><?php foreach ( $task_milestones as $milestone ) : ?><option value="<?php echo esc_attr( $milestone['id'] ); ?>" <?php selected( (int) $task['milestone_id'], (int) $milestone['id'] ); ?>><?php echo esc_html( $milestone['title'] ); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e( 'Estimated Hours', 'teamops-hub' ); ?></span><input type="number" step="0.25" min="0" name="estimated_hours" value="<?php echo esc_attr( $task['estimated_hours'] ); ?>" /></label><?php endif; ?></div><label class="teamops-front-field-stack"><span><?php esc_html_e( 'Description', 'teamops-hub' ); ?></span><textarea name="description" rows="4"><?php echo esc_textarea( $task['description'] ); ?></textarea></label><button type="submit" class="teamops-front-button"><?php esc_html_e( 'Save Task Details', 'teamops-hub' ); ?></button></form></details>
			<div class="teamops-front-comments"><h4><?php esc_html_e( 'Discussion', 'teamops-hub' ); ?></h4><?php if ( empty( $comments ) ) : ?><p><?php esc_html_e( 'No comments yet.', 'teamops-hub' ); ?></p><?php else : ?><ul class="teamops-front-comment-list"><?php foreach ( $comments as $comment ) : ?><li><div class="teamops-front-comment-meta"><strong><?php echo esc_html( $comment['display_name'] ?: $comment['user_login'] ); ?></strong><small><?php echo esc_html( $comment['created_at'] ); ?></small></div><div class="teamops-front-comment-body"><?php echo wp_kses_post( wpautop( esc_html( $comment['content'] ) ) ); ?></div></li><?php endforeach; ?></ul><?php endif; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-front-comment-form"><?php wp_nonce_field( 'teamops_hub_front_save_comment' ); ?><input type="hidden" name="action" value="teamops_hub_front_save_comment" /><input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" /><input type="hidden" name="redirect_url" value="<?php echo esc_url( $current_url ); ?>" /><label for="teamops-front-comment-<?php echo esc_attr( $task['id'] ); ?>"><?php esc_html_e( 'Add Comment', 'teamops-hub' ); ?></label><textarea id="teamops-front-comment-<?php echo esc_attr( $task['id'] ); ?>" name="content" rows="3" required></textarea><?php if ( '' !== $mention_hint ) : ?><small><?php echo esc_html( sprintf( __( 'Mention teammates with: %s', 'teamops-hub' ), $mention_hint ) ); ?></small><?php endif; ?><button type="submit" class="teamops-front-button"><?php esc_html_e( 'Post Comment', 'teamops-hub' ); ?></button></form></div>
		</section>
		<?php
		return ob_get_clean();
	}

	public function handle_status_update() {
		check_admin_referer( 'teamops_hub_front_update_task_status' );
		$task_id = absint( $_POST['task_id'] ?? 0 );
		$status = sanitize_key( $_POST['status'] ?? '' );
		$updated = $this->tasks->update_status( $task_id, $status );
		$this->redirect( array( 'teamops_notice' => $updated ? 'status_saved' : '', 'teamops_error' => $updated ? '' : __( 'The task status could not be updated.', 'teamops-hub' ) ) );
	}

	public function handle_task_update() {
		check_admin_referer( 'teamops_hub_front_update_task' );
		$task_id = absint( $_POST['task_id'] ?? 0 );
		$saved = $this->tasks->update_workspace_task( $task_id, wp_unslash( $_POST ) );
		$this->redirect( array( 'teamops_notice' => is_wp_error( $saved ) ? '' : 'task_saved', 'teamops_error' => is_wp_error( $saved ) ? $saved->get_error_message() : '' ) );
	}

	public function handle_subtask_toggle() {
		check_admin_referer( 'teamops_hub_front_toggle_subtask' );
		$subtask_id = absint( $_POST['subtask_id'] ?? 0 );
		$is_completed = ! empty( $_POST['is_completed'] );
		$updated = $this->subtasks->toggle_subtask( $subtask_id, $is_completed );
		$this->redirect( array( 'teamops_notice' => $updated ? 'subtask_saved' : '', 'teamops_error' => $updated ? '' : __( 'The checklist item could not be updated.', 'teamops-hub' ) ) );
	}

	public function handle_save_comment() {
		check_admin_referer( 'teamops_hub_front_save_comment' );
		$saved = $this->comments->save_comment( wp_unslash( $_POST ) );
		$this->redirect( array( 'teamops_notice' => is_wp_error( $saved ) ? '' : 'comment_saved', 'teamops_error' => is_wp_error( $saved ) ? implode( ' ', $saved->get_error_data() ?: array( $saved->get_error_message() ) ) : '' ) );
	}

	public function handle_mark_notifications_read() {
		check_admin_referer( 'teamops_hub_front_mark_notifications_read' );
		$updated = $this->notifications->mark_all_read_for_current_user();
		$this->redirect( array( 'teamops_notice' => $updated ? 'notifications_read' : '', 'teamops_error' => $updated ? '' : __( 'Notifications could not be updated.', 'teamops-hub' ) ) );
	}

	public function handle_kanban_move() {
		check_ajax_referer( 'teamops_hub_front_move_task', 'nonce' );
		if ( ! is_user_logged_in() || ! current_user_can( 'teamops_access' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have access to move this task.', 'teamops-hub' ) ), 403 );
		}
		$task_id = absint( $_POST['task_id'] ?? 0 );
		$status = sanitize_key( $_POST['status'] ?? '' );
		$updated = $this->tasks->update_status( $task_id, $status );
		if ( ! $updated ) {
			wp_send_json_error( array( 'message' => __( 'The task could not be moved.', 'teamops-hub' ) ), 400 );
		}
		wp_send_json_success( array( 'message' => __( 'Task moved.', 'teamops-hub' ) ) );
	}
	private function portfolio_stats( array $projects, array $tasks, array $milestones ) {
		$active_projects = count( array_filter( $projects, static function ( $project ) { return 'active' === ( $project['status'] ?? '' ); } ) );
		return array(
			'projects' => count( $projects ),
			'active_projects' => $active_projects,
			'tasks' => count( $tasks ),
			'completed_tasks' => count( array_filter( $tasks, static function ( $task ) { return ! empty( $task['completed_at'] ); } ) ),
			'overdue_tasks' => count( array_filter( $tasks, array( $this, 'is_overdue_task' ) ) ),
			'milestones' => count( $milestones ),
		);
	}

	private function project_cards( array $projects, array $all_tasks, array $milestones ) {
		$cards = array();
		foreach ( $projects as $project ) {
			$stats = $this->project_stats( $project, $all_tasks, $milestones );
			$cards[] = array(
				'id' => (int) $project['id'],
				'title' => $project['title'],
				'status_label' => ucfirst( str_replace( '_', ' ', $project['status'] ?? 'planned' ) ),
				'priority_label' => ucfirst( $project['priority'] ?? 'medium' ),
				'total_tasks' => $stats['total_tasks'],
				'open_tasks' => $stats['open_tasks'],
				'overdue_tasks' => $stats['overdue_tasks'],
				'completed_tasks' => $stats['completed_tasks'],
				'milestone_total' => $stats['milestone_total'],
				'progress' => $stats['progress'],
				'url' => $this->workspace_url( array( 'teamops_project' => (int) $project['id'], 'teamops_tab' => 'overview' ) ),
			);
		}
		return $cards;
	}

	private function project_stats( array $project, array $all_tasks, array $milestones ) {
		$project_tasks = array_values( array_filter( $all_tasks, static function ( $task ) use ( $project ) { return (int) $task['project_id'] === (int) $project['id']; } ) );
		$completed = count( array_filter( $project_tasks, static function ( $task ) { return ! empty( $task['completed_at'] ); } ) );
		$total = count( $project_tasks );
		$project_miles = $this->milestones_for_project( $milestones, (int) $project['id'] );
		return array(
			'total_tasks' => $total,
			'completed_tasks' => $completed,
			'open_tasks' => max( 0, $total - $completed ),
			'overdue_tasks' => count( array_filter( $project_tasks, array( $this, 'is_overdue_task' ) ) ),
			'milestone_total' => count( $project_miles ),
			'progress' => $total > 0 ? (int) round( ( $completed / $total ) * 100 ) : 0,
		);
	}

	private function is_overdue_task( $task ) {
		if ( empty( $task['due_date'] ) || ! empty( $task['completed_at'] ) ) {
			return false;
		}
		return strtotime( $task['due_date'] ) < strtotime( current_time( 'Y-m-d' ) );
	}

	private function project_members( array $project ) {
		if ( empty( $project['member_ids'] ) ) {
			return array();
		}
		return get_users( array( 'include' => array_map( 'intval', $project['member_ids'] ), 'orderby' => 'display_name' ) );
	}

	private function project_tabs() {
		return array(
			'overview' => __( 'Overview', 'teamops-hub' ),
			'kanban' => __( 'Kanban', 'teamops-hub' ),
			'tasks' => __( 'Tasks', 'teamops-hub' ),
			'milestones' => __( 'Milestones', 'teamops-hub' ),
			'files' => __( 'Files', 'teamops-hub' ),
		);
	}

	private function normalize_tab( $tab ) {
		$tabs = array_keys( $this->project_tabs() );
		return in_array( $tab, $tabs, true ) ? $tab : 'overview';
	}

	private function redirect( array $query_args ) {
		$redirect_url = wp_validate_redirect( wp_unslash( $_POST['redirect_url'] ?? '' ), home_url( '/' ) );
		$redirect_url = remove_query_arg( array( 'teamops_notice', 'teamops_error' ), $redirect_url );
		wp_safe_redirect( add_query_arg( $query_args, $redirect_url ) );
		exit;
	}

	private function active_filters( array $filters ) {
		return array_filter( $filters, static function ( $value ) { return '' !== $value && 0 !== $value; } );
	}

	private function project_map( array $projects ) {
		$map = array();
		foreach ( $projects as $project ) {
			$map[ (int) $project['id'] ] = $project['title'];
		}
		return $map;
	}

	private function filtered_projects( array $projects, $project_id ) {
		if ( empty( $project_id ) ) {
			return $projects;
		}
		return array_values( array_filter( $projects, static function ( $project ) use ( $project_id ) { return (int) $project['id'] === (int) $project_id; } ) );
	}

	private function milestones_for_project( array $milestones, $project_id ) {
		return array_values( array_filter( $milestones, static function ( $milestone ) use ( $project_id ) { return (int) $milestone['project_id'] === (int) $project_id; } ) );
	}

	private function users_for_project( array $options, $project_id, $assigned_user_id ) {
		$member_ids = array();
		foreach ( $options['projects'] as $project ) {
			if ( (int) $project['id'] === (int) $project_id ) {
				$member_ids = array_map( 'intval', $project['member_ids'] ?? array() );
				break;
			}
		}
		$member_ids[] = (int) $assigned_user_id;
		$member_ids = array_values( array_unique( array_filter( $member_ids ) ) );
		return array_values( array_filter( $options['users'], static function ( $user ) use ( $member_ids ) { return in_array( (int) $user->ID, $member_ids, true ); } ) );
	}

	private function kanban_columns( array $tasks, array $statuses ) {
		$columns = array();
		foreach ( $statuses as $status ) {
			$columns[ $status['status_key'] ] = array( 'key' => $status['status_key'], 'label' => $status['label'], 'color' => $status['color'] ?? '', 'tasks' => array() );
		}
		foreach ( $tasks as $task ) {
			$status_key = $task['status'] ?? '';
			if ( ! isset( $columns[ $status_key ] ) ) {
				$columns[ $status_key ] = array( 'key' => $status_key, 'label' => $task['status_label'] ?? ucfirst( str_replace( '_', ' ', $status_key ) ), 'color' => '', 'tasks' => array() );
			}
			$columns[ $status_key ]['tasks'][] = $task;
		}
		return array_values( $columns );
	}

	private function status_color( $color ) {
		$color = sanitize_hex_color( $color );
		return $color ?: '#1f6f68';
	}

	private function current_url() {
		return get_permalink() ?: home_url( '/' );
	}

	private function workspace_url( array $args = array() ) {
		$query_args = array();
		if ( ! empty( $args['teamops_project'] ) ) {
			$query_args['teamops_project'] = (int) $args['teamops_project'];
		}
		if ( ! empty( $args['teamops_tab'] ) && 'dashboard' !== $args['teamops_tab'] ) {
			$query_args['teamops_tab'] = sanitize_key( $args['teamops_tab'] );
		}
		if ( ! empty( $args['teamops_status'] ) ) {
			$query_args['teamops_status'] = sanitize_key( $args['teamops_status'] );
		}
		return add_query_arg( $query_args, $this->current_url() );
	}

	private function asset_version( $relative_path ) {
		$path = TEAMOPS_HUB_PATH . ltrim( $relative_path, '/\\' );
		if ( file_exists( $path ) ) {
			return (string) filemtime( $path );
		}
		return TEAMOPS_HUB_VERSION;
	}

	private function render_notices() {
		$notice = sanitize_key( $_GET['teamops_notice'] ?? '' );
		$error = sanitize_text_field( wp_unslash( $_GET['teamops_error'] ?? '' ) );
		$html = '';
		if ( 'status_saved' === $notice ) { $html .= '<div class="teamops-front-notice is-success"><p>' . esc_html__( 'Task status updated.', 'teamops-hub' ) . '</p></div>'; }
		if ( 'subtask_saved' === $notice ) { $html .= '<div class="teamops-front-notice is-success"><p>' . esc_html__( 'Checklist item updated.', 'teamops-hub' ) . '</p></div>'; }
		if ( 'comment_saved' === $notice ) { $html .= '<div class="teamops-front-notice is-success"><p>' . esc_html__( 'Comment added.', 'teamops-hub' ) . '</p></div>'; }
		if ( 'task_saved' === $notice ) { $html .= '<div class="teamops-front-notice is-success"><p>' . esc_html__( 'Task details updated.', 'teamops-hub' ) . '</p></div>'; }
		if ( 'notifications_read' === $notice ) { $html .= '<div class="teamops-front-notice is-success"><p>' . esc_html__( 'Notifications marked as read.', 'teamops-hub' ) . '</p></div>'; }
		if ( '' !== $error ) { $html .= '<div class="teamops-front-notice is-error"><p>' . esc_html( $error ) . '</p></div>'; }
		return $html;
	}
}
