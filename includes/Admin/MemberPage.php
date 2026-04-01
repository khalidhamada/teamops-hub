<?php
/**
 * Team member work area.
 *
 * @package TeamOpsHub\Admin
 */

namespace TeamOpsHub\Admin;

use TeamOpsHub\Services\NotificationService;
use TeamOpsHub\Services\ProjectService;
use TeamOpsHub\Services\SubtaskService;
use TeamOpsHub\Services\TaskCommentService;
use TeamOpsHub\Services\TaskService;

defined( 'ABSPATH' ) || exit;

class MemberPage {
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
	 * Notification service.
	 *
	 * @var NotificationService
	 */
	private $notifications;

	/**
	 * Constructor.
	 *
	 * @param ProjectService      $projects Project service.
	 * @param TaskService         $tasks Task service.
	 * @param SubtaskService      $subtasks Subtask service.
	 * @param TaskCommentService  $comments Comment service.
	 * @param NotificationService $notifications Notification service.
	 */
	public function __construct( ProjectService $projects, TaskService $tasks, SubtaskService $subtasks, TaskCommentService $comments, NotificationService $notifications ) {
		$this->projects      = $projects;
		$this->tasks         = $tasks;
		$this->subtasks      = $subtasks;
		$this->comments      = $comments;
		$this->notifications = $notifications;

		add_action( 'admin_post_teamops_hub_update_task_status', array( $this, 'handle_status_update' ) );
		add_action( 'admin_post_teamops_hub_toggle_subtask', array( $this, 'handle_subtask_toggle' ) );
		add_action( 'admin_post_teamops_hub_mark_notifications_read', array( $this, 'handle_mark_notifications_read' ) );
	}

	/**
	 * Renders member view.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'teamops_access' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		$projects           = $this->projects->get_projects();
		$tasks              = $this->tasks->get_tasks();
		$options            = $this->tasks->form_options();
		$notifications      = $this->notifications->recent_for_current_user( 8 );
		$unread_count       = $this->notifications->unread_count_for_current_user();

		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'My Work', 'teamops-hub' ); ?></h1>
			<?php $this->render_notices(); ?>
			<div class="teamops-grid">
				<div class="teamops-card">
					<div class="teamops-section-header">
						<div>
							<h2><?php esc_html_e( 'Notifications', 'teamops-hub' ); ?></h2>
							<p><?php echo esc_html( sprintf( __( '%d unread notifications', 'teamops-hub' ), (int) $unread_count ) ); ?></p>
						</div>
						<?php if ( $unread_count > 0 ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'teamops_hub_mark_notifications_read' ); ?>
								<input type="hidden" name="action" value="teamops_hub_mark_notifications_read" />
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Mark All Read', 'teamops-hub' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
					<?php if ( empty( $notifications ) ) : ?>
						<p><?php esc_html_e( 'No notifications yet.', 'teamops-hub' ); ?></p>
					<?php else : ?>
						<ul class="teamops-notification-list">
							<?php foreach ( $notifications as $notification ) : ?>
								<li class="<?php echo ! empty( $notification['is_read'] ) ? 'teamops-notification-read' : 'teamops-notification-unread'; ?>">
									<strong><?php echo esc_html( $notification['title'] ); ?></strong>
									<p><?php echo esc_html( $notification['body'] ); ?></p>
									<small><?php echo esc_html( $notification['created_at'] ); ?></small>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Assigned Projects', 'teamops-hub' ); ?></h2>
					<?php if ( empty( $projects ) ) : ?>
						<p><?php esc_html_e( 'No assigned projects found.', 'teamops-hub' ); ?></p>
					<?php else : ?>
						<ul class="teamops-project-list">
							<?php foreach ( $projects as $project ) : ?>
								<li>
									<strong><?php echo esc_html( $project['title'] ); ?></strong>
									<span><?php echo esc_html( ucfirst( str_replace( '_', ' ', $project['status'] ) ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Assigned Tasks', 'teamops-hub' ); ?></h2>
					<?php if ( empty( $tasks ) ) : ?>
						<p><?php esc_html_e( 'No assigned tasks found.', 'teamops-hub' ); ?></p>
					<?php else : ?>
						<?php foreach ( $tasks as $task ) : ?>
							<div class="teamops-task-work-item">
								<div class="teamops-task-work-header">
									<div>
										<h3><?php echo esc_html( $task['title'] ); ?></h3>
										<p><?php echo esc_html( $task['status_label'] ); ?><?php if ( ! empty( $task['due_date'] ) ) : ?> | <?php echo esc_html( $task['due_date'] ); ?><?php endif; ?></p>
									</div>
									<div class="teamops-task-checklist-badge">
										<?php echo esc_html( sprintf( __( '%1$d/%2$d checklist', 'teamops-hub' ), (int) $task['checklist_summary']['completed'], (int) $task['checklist_summary']['total'] ) ); ?>
									</div>
								</div>
								<div class="teamops-task-work-actions">
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'teamops_hub_update_task_status' ); ?>
										<input type="hidden" name="action" value="teamops_hub_update_task_status" />
										<input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" />
										<select name="status">
											<?php foreach ( $options['statuses'] as $status_value => $label ) : ?>
												<option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $task['status'], $status_value ); ?>><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
										<button type="submit" class="button button-secondary"><?php esc_html_e( 'Save Status', 'teamops-hub' ); ?></button>
									</form>
								</div>
								<?php $subtasks = $this->subtasks->get_subtasks( (int) $task['id'] ); ?>
								<?php if ( ! empty( $subtasks ) ) : ?>
									<ul class="teamops-subtask-list">
										<?php foreach ( $subtasks as $subtask ) : ?>
											<li>
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-subtask-toggle-form">
													<?php wp_nonce_field( 'teamops_hub_toggle_subtask' ); ?>
													<input type="hidden" name="action" value="teamops_hub_toggle_subtask" />
													<input type="hidden" name="subtask_id" value="<?php echo esc_attr( $subtask['id'] ); ?>" />
													<input type="hidden" name="is_completed" value="<?php echo esc_attr( empty( $subtask['is_completed'] ) ? 1 : 0 ); ?>" />
													<button type="submit" class="button-link teamops-subtask-toggle"><?php echo ! empty( $subtask['is_completed'] ) ? esc_html__( 'Completed', 'teamops-hub' ) : esc_html__( 'Open', 'teamops-hub' ); ?></button>
												</form>
												<span class="<?php echo ! empty( $subtask['is_completed'] ) ? 'teamops-subtask-complete' : ''; ?>"><?php echo esc_html( $subtask['title'] ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<?php $this->render_comments_section( $task ); ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles status update submission.
	 *
	 * @return void
	 */
	public function handle_status_update() {
		check_admin_referer( 'teamops_hub_update_task_status' );

		$task_id = absint( $_POST['task_id'] ?? 0 );
		$status  = sanitize_key( $_POST['status'] ?? '' );
		$updated = $this->tasks->update_status( $task_id, $status );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'teamops-hub-my-work',
					'updated' => $updated ? 1 : 0,
					'error'   => $updated ? 0 : 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handles subtask toggle submission.
	 *
	 * @return void
	 */
	public function handle_subtask_toggle() {
		check_admin_referer( 'teamops_hub_toggle_subtask' );

		$subtask_id   = absint( $_POST['subtask_id'] ?? 0 );
		$is_completed = ! empty( $_POST['is_completed'] );
		$updated      = $this->subtasks->toggle_subtask( $subtask_id, $is_completed );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'teamops-hub-my-work',
					'subtask_updated' => $updated ? 1 : 0,
					'error'           => $updated ? 0 : 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Marks all notifications as read for the current user.
	 *
	 * @return void
	 */
	public function handle_mark_notifications_read() {
		check_admin_referer( 'teamops_hub_mark_notifications_read' );

		$updated = $this->notifications->mark_all_read_for_current_user();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'               => 'teamops-hub-my-work',
					'notifications_read' => $updated ? 1 : 0,
					'error'              => $updated ? 0 : 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders notices.
	 *
	 * @return void
	 */
	private function render_notices() {
		$error_message = sanitize_text_field( wp_unslash( $_GET['teamops_error'] ?? '' ) );

		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Task status updated.', 'teamops-hub' ) . '</p></div>';
		}

		if ( ! empty( $_GET['subtask_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Checklist item updated.', 'teamops-hub' ) . '</p></div>';
		}

		if ( ! empty( $_GET['comment_saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Comment added.', 'teamops-hub' ) . '</p></div>';
		}

		if ( ! empty( $_GET['notifications_read'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notifications marked as read.', 'teamops-hub' ) . '</p></div>';
		}

		if ( ! empty( $_GET['error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The requested update could not be completed.', 'teamops-hub' ) . '</p></div>';
		}

		if ( '' !== $error_message ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error_message ) . '</p></div>';
		}
	}

	/**
	 * Renders the task comment thread and comment form.
	 *
	 * @param array $task Visible task row.
	 * @return void
	 */
	private function render_comments_section( array $task ) {
		$comments     = $this->comments->get_comments( (int) $task['id'] );
		$mention_hint = $this->comments->mention_hint( (int) $task['id'] );
		?>
		<div class="teamops-comment-section">
			<h4><?php esc_html_e( 'Discussion', 'teamops-hub' ); ?></h4>
			<?php if ( empty( $comments ) ) : ?>
				<p><?php esc_html_e( 'No comments yet.', 'teamops-hub' ); ?></p>
			<?php else : ?>
				<ul class="teamops-comment-list">
					<?php foreach ( $comments as $comment ) : ?>
						<li>
							<div class="teamops-comment-meta">
								<strong><?php echo esc_html( $comment['display_name'] ?: $comment['user_login'] ); ?></strong>
								<small><?php echo esc_html( $comment['created_at'] ); ?></small>
							</div>
							<div class="teamops-comment-body"><?php echo wp_kses_post( wpautop( esc_html( $comment['content'] ) ) ); ?></div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-form-card teamops-mt-16">
				<?php wp_nonce_field( 'teamops_hub_save_task_comment' ); ?>
				<input type="hidden" name="action" value="teamops_hub_save_task_comment" />
				<input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>" />
				<input type="hidden" name="redirect_page" value="teamops-hub-my-work" />
				<p><label for="teamops-member-comment-<?php echo esc_attr( $task['id'] ); ?>"><?php esc_html_e( 'Add Comment', 'teamops-hub' ); ?></label><textarea id="teamops-member-comment-<?php echo esc_attr( $task['id'] ); ?>" class="large-text teamops-input-full" rows="3" name="content" required></textarea></p>
				<?php if ( '' !== $mention_hint ) : ?>
					<p><small><?php echo esc_html( sprintf( __( 'Mention teammates with: %s', 'teamops-hub' ), $mention_hint ) ); ?></small></p>
				<?php endif; ?>
				<?php submit_button( __( 'Post Comment', 'teamops-hub' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
