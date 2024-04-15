<?php
/**
 * @since      1.1.0
 * @package    feedme
 * @subpackage feedme/includes
 * @author     Rohit Sharma
 */

class feedme_importer {
	protected $loader,$plugin_name,$version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.1.0
	 */

	public function __construct() {
		$this->plugin_name 	= 'feedme';
		$this->version 		= '1.1.0';
		$this->load_dependencies();
	}

	/**
	 * @description  Load the required dependencies for this plugin.
	 *
	 * @since    1.1.0
	 * @access   private
	 * @author   Rohit Sharma
	 */

	//	Include Bridge File
	private function load_dependencies() {
		if ( file_exists( plugin_dir_path( __DIR__ ) . 'includes/cmb/init.php' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes/cmb/init.php';
		}elseif ( file_exists( plugin_dir_path( __DIR__ ) . 'includes/CMB/init.php' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'includes/CMB/init.php';
		}

		//	Include Background processes
		require_once plugin_dir_path( __DIR__ ) . 'includes/classes/wp-async-request.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/classes/wp-background-process.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/classes/feed-async.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/classes/feed-item-dispatch.php';

		require_once plugin_dir_path( __DIR__ ) . 'admin/class-feedme-admin.php';
		require_once plugin_dir_path( __DIR__ ).'includes/class-feedme-helper.php';

		//	Include All Feedme Post Types
		require_once plugin_dir_path( __DIR__ ) . 'includes/CPT/feedme_feed.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/CPT/feedme_feed_item.php';
	}

	/**
	 * @description The name of the plugin used to uniquely identify it within the context of
	 *	  			WordPress and to define internationalization functionality.
	 *
	 * @since     1.1.0
	 * @return    string    The name of the plugin.
	 * @author    Rohit Sharma
	 */

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * @description Retrieve the version number of the plugin.
	 *
	 * @since     1.1.0
	 * @return    string    The version number of the plugin.
	 * @author    Rohit Sharma
	 */

	public function get_version() {
		return $this->version;
	}
}