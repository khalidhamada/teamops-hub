<?php
/**
 * Plugin settings page.
 *
 * @package TeamOpsHub\Admin
 */

namespace TeamOpsHub\Admin;

use TeamOpsHub\Services\TaskStatusService;

defined( 'ABSPATH' ) || exit;

class SettingsPage {
	/**
	 * Task status service.
	 *
	 * @var TaskStatusService
	 */
	private $task_statuses;

	/**
	 * Constructor.
	 *
	 * @param TaskStatusService $task_statuses Task status service.
	 */
	public function __construct( TaskStatusService $task_statuses ) {
		$this->task_statuses = $task_statuses;

		if ( did_action( 'admin_init' ) ) {
			$this->register_settings();
		} else {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		add_action( 'admin_post_teamops_hub_save_task_status', array( $this, 'handle_save_task_status' ) );
	}

	/**
	 * Registers settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'teamops_hub_settings',
			'teamops_hub_settings',
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'teamops_hub_general',
			__( 'General Settings', 'teamops-hub' ),
			'__return_false',
			'teamops-hub-settings'
		);

		add_settings_field(
			'member_view_mode',
			__( 'Member View Strategy', 'teamops-hub' ),
			array( $this, 'render_member_view_mode_field' ),
			'teamops-hub-settings',
			'teamops_hub_general'
		);
	}

	/**
	 * Sanitizes settings values.
	 *
	 * @param array $settings Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		return array(
			'member_view_mode' => sanitize_text_field( $settings['member_view_mode'] ?? 'admin' ),
		);
	}

	/**
	 * Renders settings page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'teamops_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		$statuses = $this->task_statuses->all();
		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'Settings', 'teamops-hub' ); ?></h1>
			<?php $this->render_notices(); ?>
			<div class="teamops-layout">
				<div class="teamops-panel teamops-panel-main">
					<form method="post" action="options.php" class="teamops-card teamops-form-card">
						<h2><?php esc_html_e( 'General Settings', 'teamops-hub' ); ?></h2>
						<?php
						settings_fields( 'teamops_hub_settings' );
						do_settings_sections( 'teamops-hub-settings' );
						submit_button();
						?>
					</form>
				</div>
				<div class="teamops-panel teamops-panel-side">
					<div class="teamops-card teamops-form-card">
						<h2><?php esc_html_e( 'Task Statuses', 'teamops-hub' ); ?></h2>
						<p><?php esc_html_e( 'Manage workflow columns and status choices for tasks. Existing keys can be updated safely.', 'teamops-hub' ); ?></p>
						<div class="teamops-table-wrap">
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Key', 'teamops-hub' ); ?></th>
										<th><?php esc_html_e( 'Label', 'teamops-hub' ); ?></th>
										<th><?php esc_html_e( 'Closed', 'teamops-hub' ); ?></th>
									</tr>
								</thead>
								<tbody>
								<?php foreach ( $statuses as $status ) : ?>
									<tr>
										<td><code><?php echo esc_html( $status['status_key'] ); ?></code></td>
										<td><?php echo esc_html( $status['label'] ); ?></td>
										<td><?php echo ! empty( $status['is_closed'] ) ? esc_html__( 'Yes', 'teamops-hub' ) : esc_html__( 'No', 'teamops-hub' ); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'teamops_hub_save_task_status' ); ?>
							<input type="hidden" name="action" value="teamops_hub_save_task_status" />
							<p><label for="teamops-task-status-key"><?php esc_html_e( 'Status Key', 'teamops-hub' ); ?></label><input id="teamops-task-status-key" type="text" class="regular-text teamops-input-full" name="status_key" placeholder="qa_ready" required /></p>
							<p><label for="teamops-task-status-label"><?php esc_html_e( 'Label', 'teamops-hub' ); ?></label><input id="teamops-task-status-label" type="text" class="regular-text teamops-input-full" name="label" placeholder="<?php esc_attr_e( 'QA Ready', 'teamops-hub' ); ?>" required /></p>
							<p><label for="teamops-task-status-color"><?php esc_html_e( 'Color', 'teamops-hub' ); ?></label><input id="teamops-task-status-color" type="text" class="regular-text teamops-input-full" name="color" value="#2271b1" /></p>
							<p><label for="teamops-task-status-order"><?php esc_html_e( 'Sort Order', 'teamops-hub' ); ?></label><input id="teamops-task-status-order" type="number" class="teamops-input-full" name="sort_order" value="50" min="0" step="1" /></p>
							<p><label><input type="checkbox" name="is_closed" value="1" /> <?php esc_html_e( 'Treat as closed/completed status', 'teamops-hub' ); ?></label></p>
							<?php submit_button( __( 'Save Task Status', 'teamops-hub' ) ); ?>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Saves a task status.
	 *
	 * @return void
	 */
	public function handle_save_task_status() {
		if ( ! current_user_can( 'teamops_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage workflow settings.', 'teamops-hub' ) );
		}

		check_admin_referer( 'teamops_hub_save_task_status' );

		$saved = $this->task_statuses->save_status( wp_unslash( $_POST ) );

		if ( is_wp_error( $saved ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'          => 'teamops-hub-settings',
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
					'page'           => 'teamops-hub-settings',
					'teamops_notice' => 'task_status_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders module placeholder page.
	 *
	 * @return void
	 */
	public function render_modules_placeholder() {
		if ( ! current_user_can( 'teamops_manage_settings' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'Future Modules', 'teamops-hub' ); ?></h1>
			<div class="teamops-card">
				<ul class="teamops-stat-list">
					<li><span><?php esc_html_e( 'Helpdesk / Ticketing', 'teamops-hub' ); ?></span><strong><?php esc_html_e( 'Planned', 'teamops-hub' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Document Repository', 'teamops-hub' ); ?></span><strong><?php esc_html_e( 'Planned', 'teamops-hub' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Notifications', 'teamops-hub' ); ?></span><strong><?php esc_html_e( 'Stub Ready', 'teamops-hub' ); ?></strong></li>
					<li><span><?php esc_html_e( 'Audit Trail / Activity Feed', 'teamops-hub' ); ?></span><strong><?php esc_html_e( 'Foundation Ready', 'teamops-hub' ); ?></strong></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders member view mode field.
	 *
	 * @return void
	 */
	public function render_member_view_mode_field() {
		$settings = get_option( 'teamops_hub_settings', array() );
		$value    = $settings['member_view_mode'] ?? 'admin';
		?>
		<select name="teamops_hub_settings[member_view_mode]">
			<option value="admin" <?php selected( $value, 'admin' ); ?>><?php esc_html_e( 'Restricted admin views', 'teamops-hub' ); ?></option>
			<option value="hybrid" <?php selected( $value, 'hybrid' ); ?>><?php esc_html_e( 'Hybrid admin + front-end later', 'teamops-hub' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Member execution continues in admin for now while the architecture stays ready for future hybrid screens.', 'teamops-hub' ); ?></p>
		<?php
	}

	/**
	 * Renders notices.
	 *
	 * @return void
	 */
	private function render_notices() {
		$notice = sanitize_key( $_GET['teamops_notice'] ?? '' );
		$error  = sanitize_text_field( wp_unslash( $_GET['teamops_error'] ?? '' ) );

		if ( 'task_status_saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Task status saved successfully.', 'teamops-hub' ) . '</p></div>';
		}

		if ( '' !== $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}
	}
}
