<?php
/**
 * Plugin Name: P2P to Taxonomy Migration
 * Plugin URI: https://github.com/carstingaxion/p2p-to-taxonomy-migration
 * Description: Migrate Posts 2 Posts relationships to Taxonomy
 * Version: 1.0.0
 * Author: carstingaxion
 * Author URI: https://github.com/carstingaxion
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: p2p-to-taxonomy-migration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package P2P_To_Taxonomy_Migration
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for P2P to Taxonomy Migration.
 */
class P2P_To_Taxonomy_Migration {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	const FILE = __FILE__;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	const DIR = __DIR__;

	/**
	 * Singleton instance.
	 *
	 * @var P2P_To_Taxonomy_Migration
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return P2P_To_Taxonomy_Migration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		// Load plugin textdomain for translations.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Register plugin activation and deactivation hooks.
		register_activation_hook( self::FILE, [ $this, 'on_activation' ] );
		register_deactivation_hook( self::FILE, [ $this, 'on_deactivation' ] );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'p2p-to-taxonomy-migration',
			false,
			dirname( plugin_basename( self::FILE ) ) . '/languages'
		);
	}

	/**
	 * Plugin activation hook.
	 */
	public function on_activation() {
		// Add activation logic here if needed.
	}

	/**
	 * Plugin deactivation hook.
	 */
	public function on_deactivation() {
		// Add deactivation logic here if needed.
	}
}

// Initialize the plugin.
P2P_To_Taxonomy_Migration::get_instance();
