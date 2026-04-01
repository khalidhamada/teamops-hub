<?php
/**
 * Projects admin page.
 *
 * @package TeamOpsHub\Modules\Projects\Admin
 */

namespace TeamOpsHub\Modules\Projects\Admin;

use TeamOpsHub\Services\MilestoneService;
use TeamOpsHub\Services\PermissionService;
use TeamOpsHub\Services\ProjectService;

defined( 'ABSPATH' ) || exit;

class ProjectsPage {
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

		add_action( 'admin_post_teamops_hub_save_project', array( $this, 'handle_save' ) );
		add_action( 'admin_post_teamops_hub_save_milestone', array( $this, 'handle_save_milestone' ) );
	}

	/**
	 * Registers submenu page.
	 *
	 * @return void
	 */
	public function register() {
		add_submenu_page(
			'teamops-hub',
			__( 'Projects', 'teamops-hub' ),
			__( 'Projects', 'teamops-hub' ),
			'teamops_access',
			'teamops-hub-projects',
			array( $this, 'render' )
		);
	}

	/**
	 * Renders projects page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'teamops_access' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'teamops-hub' ) );
		}

		$project_id         = absint( $_GET['project_id'] ?? 0 );
		$project            = $project_id ? $this->projects->get_project( $project_id ) : null;
		$edit_milestone_id  = absint( $_GET['edit_milestone_id'] ?? 0 );
		$options            = $this->projects->form_options();
		$filters            = array(
			'search'        => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'status'        => sanitize_key( $_GET['filter_status'] ?? '' ),
			'priority'      => sanitize_key( $_GET['filter_priority'] ?? '' ),
			'owner_user_id' => absint( $_GET['filter_owner_user_id'] ?? 0 ),
		);
		$projects           = $this->projects->get_projects( $this->active_filters( $filters ) );
		$user_map           = $this->user_map( $options['users'] );
		$metrics            = $this->project_metrics( $projects );
		$milestones         = $project ? $this->milestones->get_milestones( array( 'project_id' => $project_id ) ) : array();
		$selected_milestone = $this->selected_milestone( $milestones, $edit_milestone_id );

		?>
		<div class="wrap teamops-hub-admin">
			<h1><?php esc_html_e( 'Projects', 'teamops-hub' ); ?></h1>
			<p><?php esc_html_e( 'Manage delivery work, team ownership, and project health from one place.', 'teamops-hub' ); ?></p>
			<?php $this->render_notices(); ?>
			<div class="teamops-grid teamops-grid-compact">
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Visible Projects', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( $metrics['total'] ); ?></p>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Active', 'teamops-hub' ); ?></h2>
					<p class="teamops-big-number"><?php echo esc_html( $metrics['active'] ); ?></p>
				</div>
				<div class="teamops-card">
					<h2><?php esc_html_e( 'Due This Week', 'teamops-hub' ); ?></h2>
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
						<h2><?php esc_html_e( 'Project List', 'teamops-hub' ); ?></h2>
						<?php $this->render_filters( $options, $filters ); ?>
						<?php $this->render_projects_table( $projects, $user_map ); ?>
					</div>
					<?php if ( $project ) : ?>
						<div class="teamops-card">
							<h2><?php esc_html_e( 'Project Overview', 'teamops-hub' ); ?></h2>
							<div class="teamops-grid teamops-grid-compact">
								<div>
									<strong><?php esc_html_e( 'Progress', 'teamops-hub' ); ?></strong>
									<p class="teamops-big-number"><?php echo esc_html( $project['progress'] ); ?>%</p>
								</div>
								<div>
									<strong><?php esc_html_e( 'Owner', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( $user_map[ (int) $project['owner_user_id'] ] ?? __( 'Unassigned', 'teamops-hub' ) ); ?></p>
								</div>
								<div>
									<strong><?php esc_html_e( 'Status', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( ucfirst( str_replace( '_', ' ', $project['status'] ) ) ); ?></p>
								</div>
								<div>
									<strong><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></strong>
									<p><?php echo esc_html( $project['due_date'] ?: '-' ); ?></p>
								</div>
							</div>
							<?php if ( ! empty( $project['description'] ) ) : ?>
								<div class="teamops-richtext"><?php echo wp_kses_post( wpautop( $project['description'] ) ); ?></div>
							<?php endif; ?>
							<p><?php echo esc_html( sprintf( __( '%d related tasks', 'teamops-hub' ), count( $project['tasks'] ) ) ); ?></p>
							<?php if ( ! empty( $project['tasks'] ) ) : ?>
								<div class="teamops-table-wrap">
									<table class="widefat striped">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Task', 'teamops-hub' ); ?></th>
												<th><?php esc_html_e( 'Status', 'teamops-hub' ); ?></th>
												<th><?php esc_html_e( 'Due', 'teamops-hub' ); ?></th>
											</tr>
										</thead>
										<tbody>
										<?php foreach ( $project['tasks'] as $task ) : ?>
											<tr>
												<td><?php echo esc_html( $task['title'] ); ?></td>
												<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $task['status'] ) ) ); ?></td>
												<td><?php echo esc_html( $task['due_date'] ?: '-' ); ?></td>
											</tr>
										<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php endif; ?>
						</div>
						<div class="teamops-card">
							<h2><?php esc_html_e( 'Milestones', 'teamops-hub' ); ?></h2>
							<?php $this->render_milestone_table( $milestones, $project_id ); ?>
							<?php if ( $this->permissions->can_manage_projects() ) : ?>
								<?php $this->render_milestone_form( $project, $milestones, $selected_milestone ); ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="teamops-panel teamops-panel-side">
					<div class="teamops-card teamops-form-card">
						<h2><?php echo esc_html( $project ? __( 'Edit Project', 'teamops-hub' ) : __( 'Add Project', 'teamops-hub' ) ); ?></h2>
						<?php if ( $this->permissions->can_manage_projects() ) : ?>
							<?php $this->render_form( $options, $project ); ?>
						<?php else : ?>
							<p><?php esc_html_e( 'You can view projects assigned to you here. Project creation is limited to managers and administrators.', 'teamops-hub' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles create/update requests.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'teamops_manage_projects' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage projects.', 'teamops-hub' ) );
		}

		check_admin_referer( 'teamops_hub_save_project' );

		$project_id = absint( $_POST['project_id'] ?? 0 );
		$saved_id   = $this->projects->save_project( wp_unslash( $_POST ), $project_id ?: null );

		if ( is_wp_error( $saved_id ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'          => 'teamops-hub-projects',
						'project_id'    => $project_id,
						'teamops_error' => implode( ' ', $saved_id->get_error_data() ?: array( $saved_id->get_error_message() ) ),
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'teamops-hub-projects',
					'project_id'     => $saved_id,
					'teamops_notice' => 'project_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handles milestone save requests.
	 *
	 * @return void
	 */
	public function handle_save_milestone() {
		if ( ! current_user_can( 'teamops_manage_projects' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage milestones.', 'teamops-hub' ) );
		}

		check_admin_referer( 'teamops_hub_save_milestone' );

		$project_id   = absint( $_POST['project_id'] ?? 0 );
		$milestone_id = absint( $_POST['milestone_id'] ?? 0 );
		$saved_id     = $this->milestones->save_milestone( wp_unslash( $_POST ), $milestone_id ?: null );

		if ( is_wp_error( $saved_id ) ) {
			$error_args = array(
				'page'          => 'teamops-hub-projects',
				'project_id'    => $project_id,
				'teamops_error' => implode( ' ', $saved_id->get_error_data() ?: array( $saved_id->get_error_message() ) ),
			);

			if ( $milestone_id ) {
				$error_args['edit_milestone_id'] = $milestone_id;
			}

			wp_safe_redirect(
				add_query_arg(
					$error_args,
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => 'teamops-hub-projects',
					'project_id'        => $project_id,
					'edit_milestone_id' => false,
					'teamops_notice'    => $milestone_id ? 'milestone_updated' : 'milestone_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders project table.
	 *
	 * @param array $projects Project rows.
	 * @param array $user_map User lookup.
	 * @return void
	 */
	private function render_projects_table( array $projects, array $user_map ) {
		if ( empty( $projects ) ) {
			echo '<p>' . esc_html__( 'No projects available yet.', 'teamops-hub' ) . '</p>';
			return;
		}

		echo '<div class="teamops-table-wrap">';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'Title', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Owner', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Status', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Priority', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Due', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Action', 'teamops-hub' ) . '</th></tr></thead><tbody>';

		foreach ( $projects as $project ) {
			$link = add_query_arg(
				array(
					'page'       => 'teamops-hub-projects',
					'project_id' => $project['id'],
				),
				admin_url( 'admin.php' )
			);

			echo '<tr>';
			echo '<td>' . esc_html( $project['title'] ) . '</td>';
			echo '<td>' . esc_html( $user_map[ (int) $project['owner_user_id'] ] ?? __( 'Unassigned', 'teamops-hub' ) ) . '</td>';
			echo '<td>' . esc_html( ucfirst( str_replace( '_', ' ', $project['status'] ) ) ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $project['priority'] ) ) . '</td>';
			echo '<td>' . esc_html( $project['due_date'] ?: '-' ) . '</td>';
			echo '<td><a class="button button-small" href="' . esc_url( $link ) . '">' . esc_html__( 'Open', 'teamops-hub' ) . '</a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Renders project filters.
	 *
	 * @param array $options Form options.
	 * @param array $filters Active filters.
	 * @return void
	 */
	private function render_filters( array $options, array $filters ) {
		?>
		<form method="get" class="teamops-filters">
			<input type="hidden" name="page" value="teamops-hub-projects" />
			<input type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search projects', 'teamops-hub' ); ?>" />
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
			<select name="filter_owner_user_id">
				<option value="0"><?php esc_html_e( 'All Owners', 'teamops-hub' ); ?></option>
				<?php foreach ( $options['users'] as $user ) : ?>
					<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $filters['owner_user_id'], (int) $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button"><?php esc_html_e( 'Apply', 'teamops-hub' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Renders project form.
	 *
	 * @param array      $options Form options.
	 * @param array|null $project Project data.
	 * @return void
	 */
	private function render_form( array $options, $project = null ) {
		$project = $project ?: array(
			'id'            => 0,
			'title'         => '',
			'code'          => '',
			'description'   => '',
			'status'        => 'planned',
			'priority'      => 'medium',
			'owner_user_id' => get_current_user_id(),
			'project_type'  => '',
			'start_date'    => '',
			'due_date'      => '',
			'notes'         => '',
			'member_ids'    => array(),
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'teamops_hub_save_project' ); ?>
			<input type="hidden" name="action" value="teamops_hub_save_project" />
			<input type="hidden" name="project_id" value="<?php echo esc_attr( $project['id'] ); ?>" />
			<p><label for="teamops-project-title"><?php esc_html_e( 'Title', 'teamops-hub' ); ?></label><input id="teamops-project-title" type="text" class="regular-text teamops-input-full" name="title" value="<?php echo esc_attr( $project['title'] ); ?>" required /></p>
			<p><label for="teamops-project-code"><?php esc_html_e( 'Code', 'teamops-hub' ); ?></label><input id="teamops-project-code" type="text" class="regular-text teamops-input-full" name="code" value="<?php echo esc_attr( $project['code'] ); ?>" /></p>
			<p><label for="teamops-project-description"><?php esc_html_e( 'Description', 'teamops-hub' ); ?></label><textarea id="teamops-project-description" class="large-text teamops-input-full" rows="5" name="description"><?php echo esc_textarea( $project['description'] ); ?></textarea></p>
			<p><label for="teamops-project-status"><?php esc_html_e( 'Status', 'teamops-hub' ); ?></label><?php $this->render_select( 'status', $options['statuses'], $project['status'], 'teamops-project-status' ); ?></p>
			<p><label for="teamops-project-priority"><?php esc_html_e( 'Priority', 'teamops-hub' ); ?></label><?php $this->render_select( 'priority', $options['priorities'], $project['priority'], 'teamops-project-priority' ); ?></p>
			<p><label for="teamops-project-owner"><?php esc_html_e( 'Owner / Manager', 'teamops-hub' ); ?></label><select id="teamops-project-owner" class="teamops-input-full" name="owner_user_id"><?php foreach ( $options['users'] as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( (int) $project['owner_user_id'], (int) $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></p>
			<p><label for="teamops-project-type"><?php esc_html_e( 'Category / Type', 'teamops-hub' ); ?></label><input id="teamops-project-type" type="text" class="regular-text teamops-input-full" name="project_type" value="<?php echo esc_attr( $project['project_type'] ); ?>" /></p>
			<p><label for="teamops-project-start"><?php esc_html_e( 'Start Date', 'teamops-hub' ); ?></label><input id="teamops-project-start" type="date" class="teamops-input-full" name="start_date" value="<?php echo esc_attr( $project['start_date'] ); ?>" /></p>
			<p><label for="teamops-project-due"><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></label><input id="teamops-project-due" type="date" class="teamops-input-full" name="due_date" value="<?php echo esc_attr( $project['due_date'] ); ?>" /></p>
			<p><label for="teamops-project-members"><?php esc_html_e( 'Team Members', 'teamops-hub' ); ?></label><select id="teamops-project-members" class="teamops-input-full" name="member_ids[]" multiple size="6"><?php foreach ( $options['users'] as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( (int) $user->ID, array_map( 'intval', $project['member_ids'] ), true ) ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select><small><?php esc_html_e( 'The selected owner is automatically included in the project team.', 'teamops-hub' ); ?></small></p>
			<p><label for="teamops-project-notes"><?php esc_html_e( 'Notes', 'teamops-hub' ); ?></label><textarea id="teamops-project-notes" class="large-text teamops-input-full" rows="4" name="notes"><?php echo esc_textarea( $project['notes'] ); ?></textarea></p>
			<?php submit_button( $project['id'] ? __( 'Update Project', 'teamops-hub' ) : __( 'Create Project', 'teamops-hub' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Renders milestone table.
	 *
	 * @param array $milestones Milestone rows.
	 * @param int   $project_id Current project id.
	 * @return void
	 */
	private function render_milestone_table( array $milestones, $project_id ) {
		if ( empty( $milestones ) ) {
			echo '<p>' . esc_html__( 'No milestones added yet.', 'teamops-hub' ) . '</p>';
			return;
		}

		echo '<div class="teamops-table-wrap">';
		echo '<table class="widefat striped">';
		echo '<thead><tr><th>' . esc_html__( 'Milestone', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Status', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Due', 'teamops-hub' ) . '</th><th>' . esc_html__( 'Action', 'teamops-hub' ) . '</th></tr></thead><tbody>';

		foreach ( $milestones as $milestone ) {
			$edit_link = add_query_arg(
				array(
					'page'              => 'teamops-hub-projects',
					'project_id'        => $project_id,
					'edit_milestone_id' => (int) $milestone['id'],
				),
				admin_url( 'admin.php' )
			);

			echo '<tr>';
			echo '<td>' . esc_html( $milestone['title'] ) . '</td>';
			echo '<td>' . esc_html( ucwords( str_replace( '_', ' ', $milestone['status'] ) ) ) . '</td>';
			echo '<td>' . esc_html( $milestone['due_date'] ?: '-' ) . '</td>';
			echo '<td>';

			if ( $this->permissions->can_manage_projects() ) {
				echo '<a class="button button-small" href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'teamops-hub' ) . '</a>';
			} else {
				echo esc_html__( 'View', 'teamops-hub' );
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Renders milestone add/edit form.
	 *
	 * @param array      $project Current project.
	 * @param array      $milestones Existing milestone rows.
	 * @param array|null $milestone Milestone being edited.
	 * @return void
	 */
	private function render_milestone_form( array $project, array $milestones, $milestone = null ) {
		$milestone = $milestone ?: array(
			'id'          => 0,
			'title'       => '',
			'description' => '',
			'status'      => 'planned',
			'due_date'    => '',
			'sort_order'  => count( $milestones ) + 1,
		);
		$cancel_link = add_query_arg(
			array(
				'page'       => 'teamops-hub-projects',
				'project_id' => (int) $project['id'],
			),
			admin_url( 'admin.php' )
		);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="teamops-form-card teamops-mt-16">
			<?php wp_nonce_field( 'teamops_hub_save_milestone' ); ?>
			<input type="hidden" name="action" value="teamops_hub_save_milestone" />
			<input type="hidden" name="project_id" value="<?php echo esc_attr( $project['id'] ); ?>" />
			<input type="hidden" name="milestone_id" value="<?php echo esc_attr( $milestone['id'] ); ?>" />
			<h3><?php echo esc_html( $milestone['id'] ? __( 'Edit Milestone', 'teamops-hub' ) : __( 'Add Milestone', 'teamops-hub' ) ); ?></h3>
			<p><label for="teamops-milestone-title"><?php esc_html_e( 'Milestone Title', 'teamops-hub' ); ?></label><input id="teamops-milestone-title" type="text" class="regular-text teamops-input-full" name="title" value="<?php echo esc_attr( $milestone['title'] ); ?>" required /></p>
			<p><label for="teamops-milestone-description"><?php esc_html_e( 'Description', 'teamops-hub' ); ?></label><textarea id="teamops-milestone-description" class="large-text teamops-input-full" rows="3" name="description"><?php echo esc_textarea( $milestone['description'] ); ?></textarea></p>
			<p><label for="teamops-milestone-status"><?php esc_html_e( 'Status', 'teamops-hub' ); ?></label><select id="teamops-milestone-status" class="teamops-input-full" name="status"><?php foreach ( $this->milestones->status_options() as $status_value => $label ) : ?><option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $milestone['status'], $status_value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></p>
			<p><label for="teamops-milestone-due"><?php esc_html_e( 'Due Date', 'teamops-hub' ); ?></label><input id="teamops-milestone-due" type="date" class="teamops-input-full" name="due_date" value="<?php echo esc_attr( $milestone['due_date'] ); ?>" /></p>
			<p><label for="teamops-milestone-order"><?php esc_html_e( 'Sort Order', 'teamops-hub' ); ?></label><input id="teamops-milestone-order" type="number" class="teamops-input-full" name="sort_order" value="<?php echo esc_attr( $milestone['sort_order'] ); ?>" min="0" step="1" /></p>
			<?php submit_button( $milestone['id'] ? __( 'Update Milestone', 'teamops-hub' ) : __( 'Add Milestone', 'teamops-hub' ) ); ?>
			<?php if ( $milestone['id'] ) : ?>
				<p><a href="<?php echo esc_url( $cancel_link ); ?>" class="button-link"><?php esc_html_e( 'Cancel milestone editing', 'teamops-hub' ); ?></a></p>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * Renders a select field.
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

		if ( 'project_saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Project saved successfully.', 'teamops-hub' ) . '</p></div>';
		}

		if ( 'milestone_saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Milestone saved successfully.', 'teamops-hub' ) . '</p></div>';
		}

		if ( 'milestone_updated' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Milestone updated successfully.', 'teamops-hub' ) . '</p></div>';
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
	 * Calculates project metrics from visible rows.
	 *
	 * @param array $projects Project rows.
	 * @return array
	 */
	private function project_metrics( array $projects ) {
		$today    = current_time( 'Y-m-d' );
		$due_soon = gmdate( 'Y-m-d', strtotime( '+7 days', strtotime( $today ) ) );
		$metrics  = array(
			'total'    => count( $projects ),
			'active'   => 0,
			'due_soon' => 0,
			'overdue'  => 0,
		);

		foreach ( $projects as $project ) {
			if ( 'active' === $project['status'] ) {
				++$metrics['active'];
			}

			if ( ! empty( $project['due_date'] ) && $project['due_date'] < $today && ! in_array( $project['status'], array( 'completed', 'archived' ), true ) ) {
				++$metrics['overdue'];
			}

			if ( ! empty( $project['due_date'] ) && $project['due_date'] >= $today && $project['due_date'] <= $due_soon && ! in_array( $project['status'], array( 'completed', 'archived' ), true ) ) {
				++$metrics['due_soon'];
			}
		}

		return $metrics;
	}

	/**
	 * Returns the milestone selected for editing when it belongs to the project.
	 *
	 * @param array $milestones Project milestone rows.
	 * @param int   $milestone_id Requested milestone id.
	 * @return array|null
	 */
	private function selected_milestone( array $milestones, $milestone_id ) {
		if ( empty( $milestone_id ) ) {
			return null;
		}

		foreach ( $milestones as $milestone ) {
			if ( (int) $milestone['id'] === (int) $milestone_id ) {
				return $milestone;
			}
		}

		return null;
	}
}
