<?php
/**
 * Admin page for P2P to Taxonomy Migration
 *
 * Displays the migration interface and status for converting
 * Posts 2 Posts relationships to taxonomy terms.
 *
 * @package P2P_To_Taxonomy_Migration
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the migration admin page
 *
 * @since 1.0.0
 * @return void
 */
function p2p_taxonomy_migration_render_page() {
	// Check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'p2p-to-taxonomy-migration' ) );
	}

	// Get migration status
	$migration_status = get_option( 'p2p_taxonomy_migration_status', 'not_started' );
	$migration_progress = get_option( 'p2p_taxonomy_migration_progress', array() );
	$migration_log = get_option( 'p2p_taxonomy_migration_log', array() );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php if ( isset( $_GET['migration_status'] ) ) : ?>
			<?php if ( 'success' === sanitize_key( $_GET['migration_status'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Migration completed successfully!', 'p2p-to-taxonomy-migration' ); ?></p>
				</div>
			<?php elseif ( 'error' === sanitize_key( $_GET['migration_status'] ) ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'An error occurred during migration. Please check the logs below.', 'p2p-to-taxonomy-migration' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="card">
			<h2><?php esc_html_e( 'Migration Status', 'p2p-to-taxonomy-migration' ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'Current Status:', 'p2p-to-taxonomy-migration' ); ?></strong>
				<span class="migration-status-badge" data-status="<?php echo esc_attr( $migration_status ); ?>">
					<?php echo esc_html( ucfirst( str_replace( '_', ' ', $migration_status ) ) ); ?>
				</span>
			</p>

			<?php if ( ! empty( $migration_progress ) ) : ?>
				<div class="migration-progress">
					<h3><?php esc_html_e( 'Progress', 'p2p-to-taxonomy-migration' ); ?></h3>
					<div class="progress-bar">
						<div class="progress-fill" style="width: <?php echo esc_attr( isset( $migration_progress['percentage'] ) ? $migration_progress['percentage'] : 0 ); ?>%"></div>
					</div>
					<p>
						<?php
						printf(
							esc_html__( 'Processed: %d / %d items', 'p2p-to-taxonomy-migration' ),
							isset( $migration_progress['processed'] ) ? intval( $migration_progress['processed'] ) : 0,
							isset( $migration_progress['total'] ) ? intval( $migration_progress['total'] ) : 0
						);
						?>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<div class="card">
			<h2><?php esc_html_e( 'Migration Actions', 'p2p-to-taxonomy-migration' ); ?></h2>
			<?php if ( 'completed' !== $migration_status ) : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'p2p_taxonomy_migration_action', 'p2p_migration_nonce' ); ?>
					<p>
						<button type="submit" name="p2p_migration_action" value="start_migration" class="button button-primary">
							<?php esc_html_e( 'Start Migration', 'p2p-to-taxonomy-migration' ); ?>
						</button>
						<?php if ( 'in_progress' === $migration_status ) : ?>
							<button type="submit" name="p2p_migration_action" value="pause_migration" class="button">
								<?php esc_html_e( 'Pause Migration', 'p2p-to-taxonomy-migration' ); ?>
							</button>
						<?php endif; ?>
					</p>
				</form>
			<?php else : ?>
				<p><?php esc_html_e( 'Migration has been completed.', 'p2p-to-taxonomy-migration' ); ?></p>
				<form method="post" action="">
					<?php wp_nonce_field( 'p2p_taxonomy_migration_action', 'p2p_migration_nonce' ); ?>
					<p>
						<button type="submit" name="p2p_migration_action" value="reset_migration" class="button button-secondary">
							<?php esc_html_e( 'Reset Migration', 'p2p-to-taxonomy-migration' ); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $migration_log ) ) : ?>
			<div class="card">
				<h2><?php esc_html_e( 'Migration Log', 'p2p-to-taxonomy-migration' ); ?></h2>
				<div class="migration-log">
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Timestamp', 'p2p-to-taxonomy-migration' ); ?></th>
								<th><?php esc_html_e( 'Type', 'p2p-to-taxonomy-migration' ); ?></th>
								<th><?php esc_html_e( 'Message', 'p2p-to-taxonomy-migration' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $migration_log, -50 ) as $log_entry ) : ?>
								<tr>
									<td><?php echo esc_html( isset( $log_entry['timestamp'] ) ? $log_entry['timestamp'] : '' ); ?></td>
									<td>
										<span class="log-type <?php echo esc_attr( 'log-type-' . isset( $log_entry['type'] ) ? $log_entry['type'] : 'info' ); ?>">
											<?php echo esc_html( isset( $log_entry['type'] ) ? ucfirst( $log_entry['type'] ) : 'Info' ); ?>
										</span>
									</td>
									<td><?php echo esc_html( isset( $log_entry['message'] ) ? $log_entry['message'] : '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

		<div class="card">
			<h2><?php esc_html_e( 'Settings', 'p2p-to-taxonomy-migration' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'p2p_taxonomy_migration_settings', 'p2p_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="batch_size"><?php esc_html_e( 'Batch Size', 'p2p-to-taxonomy-migration' ); ?></label>
						</th>
						<td>
							<input type="number" id="batch_size" name="batch_size" value="<?php echo esc_attr( get_option( 'p2p_taxonomy_migration_batch_size', 50 ) ); ?>" min="1" max="1000" />
							<p class="description"><?php esc_html_e( 'Number of items to process per batch during migration.', 'p2p-to-taxonomy-migration' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'p2p-to-taxonomy-migration' ) ); ?>
			</form>
		</div>
	</div>

	<style>
		.migration-status-badge {
			padding: 4px 8px;
			border-radius: 3px;
			display: inline-block;
			font-weight: 600;
		}

		.migration-status-badge[data-status="completed"],
		.migration-status-badge[data-status="success"] {
			background-color: #d4edda;
			color: #155724;
		}

		.migration-status-badge[data-status="in_progress"] {
			background-color: #cfe2ff;
			color: #084298;
		}

		.migration-status-badge[data-status="paused"] {
			background-color: #fff3cd;
			color: #664d03;
		}

		.migration-status-badge[data-status="error"],
		.migration-status-badge[data-status="failed"] {
			background-color: #f8d7da;
			color: #842029;
		}

		.migration-status-badge[data-status="not_started"] {
			background-color: #e2e3e5;
			color: #383d41;
		}

		.migration-progress {
			margin: 15px 0;
		}

		.progress-bar {
			width: 100%;
			height: 30px;
			background-color: #f0f0f0;
			border-radius: 3px;
			overflow: hidden;
			margin: 10px 0;
		}

		.progress-fill {
			height: 100%;
			background-color: #0073aa;
			transition: width 0.3s ease;
		}

		.migration-log {
			max-height: 400px;
			overflow-y: auto;
		}

		.log-type {
			padding: 2px 6px;
			border-radius: 2px;
			font-size: 12px;
			font-weight: 600;
		}

		.log-type-success {
			background-color: #d4edda;
			color: #155724;
		}

		.log-type-error {
			background-color: #f8d7da;
			color: #842029;
		}

		.log-type-warning {
			background-color: #fff3cd;
			color: #664d03;
		}

		.log-type-info {
			background-color: #d1ecf1;
			color: #0c5460;
		}
	</style>
	<?php
}
