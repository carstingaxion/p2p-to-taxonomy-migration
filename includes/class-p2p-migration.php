<?php
/**
 * P2P_Migration Class
 *
 * Handles the migration logic for converting Posts 2 Posts relationships to taxonomy terms.
 *
 * @package P2P_To_Taxonomy_Migration
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class P2P_Migration
 *
 * Manages the migration of Posts 2 Posts (P2P) relationships to WordPress taxonomy.
 *
 * @since 1.0.0
 */
class P2P_Migration {

	/**
	 * Source post type for P2P relationships
	 *
	 * @var string
	 */
	private $source_post_type;

	/**
	 * Target taxonomy slug
	 *
	 * @var string
	 */
	private $target_taxonomy;

	/**
	 * P2P connection type
	 *
	 * @var string
	 */
	private $p2p_connection_type;

	/**
	 * Migration status and logging
	 *
	 * @var array
	 */
	private $migration_log = array();

	/**
	 * Constructor
	 *
	 * @param string $source_post_type The source post type for P2P relationships
	 * @param string $target_taxonomy The target taxonomy slug
	 * @param string $p2p_connection_type The P2P connection type
	 */
	public function __construct( $source_post_type, $target_taxonomy, $p2p_connection_type ) {
		$this->source_post_type      = sanitize_key( $source_post_type );
		$this->target_taxonomy       = sanitize_key( $target_taxonomy );
		$this->p2p_connection_type   = sanitize_key( $p2p_connection_type );
	}

	/**
	 * Get migration log
	 *
	 * @return array Migration log entries
	 */
	public function get_log() {
		return $this->migration_log;
	}

	/**
	 * Add log entry
	 *
	 * @param string $message Log message
	 * @param string $level Log level (info, warning, error, success)
	 */
	private function log( $message, $level = 'info' ) {
		$this->migration_log[] = array(
			'message'   => $message,
			'level'     => $level,
			'timestamp' => current_time( 'mysql' ),
		);
	}

	/**
	 * Initialize migration
	 *
	 * Checks prerequisites and prepares for migration
	 *
	 * @return bool True if initialization successful, false otherwise
	 */
	public function initialize() {
		$this->log( 'Starting P2P Migration initialization', 'info' );

		// Check if P2P plugin is active
		if ( ! $this->is_p2p_active() ) {
			$this->log( 'Posts 2 Posts plugin is not active', 'error' );
			return false;
		}

		// Check if taxonomy exists
		if ( ! taxonomy_exists( $this->target_taxonomy ) ) {
			$this->log( 'Target taxonomy does not exist: ' . $this->target_taxonomy, 'error' );
			return false;
		}

		// Check if post type exists
		if ( ! post_type_exists( $this->source_post_type ) ) {
			$this->log( 'Source post type does not exist: ' . $this->source_post_type, 'error' );
			return false;
		}

		$this->log( 'Initialization successful', 'success' );
		return true;
	}

	/**
	 * Check if Posts 2 Posts plugin is active
	 *
	 * @return bool True if P2P is active, false otherwise
	 */
	private function is_p2p_active() {
		return function_exists( 'p2p_register_connection_type' ) || class_exists( 'P2P_Connection_Type' );
	}

	/**
	 * Get P2P connections for posts
	 *
	 * @return array Array of P2P connections
	 */
	private function get_p2p_connections() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}p2p 
			WHERE p2p_type = %s",
			$this->p2p_connection_type
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Migrate P2P relationships to taxonomy
	 *
	 * @param int|null $batch_size Optional. Number of items to process per batch. Default null (all)
	 * @return array Migration results
	 */
	public function migrate( $batch_size = null ) {
		$this->log( 'Starting migration process', 'info' );

		if ( ! $this->initialize() ) {
			return array(
				'success'      => false,
				'message'      => 'Migration initialization failed',
				'posts_failed' => 0,
				'posts_migrated' => 0,
				'log'          => $this->get_log(),
			);
		}

		$connections = $this->get_p2p_connections();

		if ( empty( $connections ) ) {
			$this->log( 'No P2P connections found for type: ' . $this->p2p_connection_type, 'warning' );
			return array(
				'success'        => true,
				'message'        => 'No P2P connections to migrate',
				'posts_failed'   => 0,
				'posts_migrated' => 0,
				'log'            => $this->get_log(),
			);
		}

		$this->log( 'Found ' . count( $connections ) . ' P2P connections', 'info' );

		$migrated_count = 0;
		$failed_count   = 0;

		foreach ( $connections as $connection ) {
			if ( $this->migrate_connection( $connection ) ) {
				$migrated_count++;
			} else {
				$failed_count++;
			}

			// Handle batch processing if batch_size is set
			if ( $batch_size && ( $migrated_count + $failed_count ) % $batch_size === 0 ) {
				$this->log( 'Batch processed: ' . ( $migrated_count + $failed_count ) . ' items', 'info' );
			}
		}

		$this->log( 'Migration completed. Migrated: ' . $migrated_count . ', Failed: ' . $failed_count, 'success' );

		return array(
			'success'         => true,
			'message'         => 'Migration completed',
			'posts_migrated'  => $migrated_count,
			'posts_failed'    => $failed_count,
			'log'             => $this->get_log(),
		);
	}

	/**
	 * Migrate a single P2P connection
	 *
	 * @param array $connection P2P connection data
	 * @return bool True if migration successful, false otherwise
	 */
	private function migrate_connection( $connection ) {
		$post_id = intval( $connection['p2p_from'] );
		$related_post_id = intval( $connection['p2p_to'] );

		// Verify post exists and has correct post type
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $this->source_post_type ) {
			$this->log( 'Invalid post ID: ' . $post_id, 'warning' );
			return false;
		}

		// Get the term for the related post
		$term_id = $this->get_or_create_term_for_post( $related_post_id );

		if ( ! $term_id ) {
			$this->log( 'Failed to create/get term for post ID: ' . $related_post_id, 'error' );
			return false;
		}

		// Assign term to post
		$result = wp_set_post_terms( $post_id, intval( $term_id ), $this->target_taxonomy, true );

		if ( is_wp_error( $result ) ) {
			$this->log( 'Error assigning term to post ' . $post_id . ': ' . $result->get_error_message(), 'error' );
			return false;
		}

		$this->log( 'Migrated P2P connection for post ' . $post_id . ' to term ' . $term_id, 'info' );
		return true;
	}

	/**
	 * Get or create a taxonomy term for a post
	 *
	 * @param int $post_id The post ID to create a term for
	 * @return int|false Term ID on success, false on failure
	 */
	private function get_or_create_term_for_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// Check if term already exists
		$existing_term = term_exists( $post->post_title, $this->target_taxonomy );

		if ( $existing_term ) {
			return intval( $existing_term['term_id'] );
		}

		// Create new term
		$new_term = wp_insert_term(
			$post->post_title,
			$this->target_taxonomy,
			array(
				'description' => 'Migrated from post ID: ' . $post_id,
			)
		);

		if ( is_wp_error( $new_term ) ) {
			return false;
		}

		return intval( $new_term['term_id'] );
	}

	/**
	 * Rollback migration
	 *
	 * @return bool True if rollback successful, false otherwise
	 */
	public function rollback() {
		$this->log( 'Starting migration rollback', 'warning' );

		// Get all posts of the source type
		$posts = get_posts(
			array(
				'post_type'      => $this->source_post_type,
				'numberposts'    => -1,
				'posts_per_page' => -1,
			)
		);

		$rollback_count = 0;

		foreach ( $posts as $post ) {
			// Remove all terms from this post in the target taxonomy
			$result = wp_set_post_terms( $post->ID, array(), $this->target_taxonomy, false );

			if ( ! is_wp_error( $result ) ) {
				$rollback_count++;
			}
		}

		$this->log( 'Rollback completed. Removed ' . $rollback_count . ' term assignments', 'success' );

		return true;
	}

	/**
	 * Get migration statistics
	 *
	 * @return array Migration statistics
	 */
	public function get_statistics() {
		$connections = $this->get_p2p_connections();
		$posts_count = count(
			array_unique(
				array_merge(
					wp_list_pluck( $connections, 'p2p_from' ),
					wp_list_pluck( $connections, 'p2p_to' )
				)
			)
		);

		return array(
			'total_connections' => count( $connections ),
			'total_posts_involved' => $posts_count,
			'connection_type' => $this->p2p_connection_type,
			'source_post_type' => $this->source_post_type,
			'target_taxonomy' => $this->target_taxonomy,
		);
	}
}
