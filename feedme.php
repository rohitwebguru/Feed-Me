<?php
/**
 * Plugin Name:       Feed Me
 * Plugin URI:        #
 * Description:       A Wordpress plugin that creates posts based on external RSS feeds. The user can define as many external RSS feed
 * 					  as desired. The plugin will periodically scan and process such feeds, creating draft posts while avoiding duplication.
 * Version:           1.1.0
 * Author:            Kvell Technologies
 * Author URI:        http://kvelltechnologies.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       feedme
 * Domain Path:       /languages
 */
global $feeditemdispatcher;
if ( !defined( 'ABSPATH' ) ) exit;
global $feedme_admin_obj;
define( 'FEEDME_APP_DIRNAME', basename( dirname( __FILE__ ) ) );
define( 'FEEDME_APP_RELPATH', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );
define( 'FEEDME_APP_PATH', plugin_dir_path( __FILE__ ) );
define( 'FEEDME_APP_URL', plugin_dir_url( __FILE__ ) );
define( 'FEEDME_APP_PREFIX', 'app' );

/**
 *	@descripiton 	This function wil import the listing table on activation
 *	@param 			NONE
 *	@return 		NONE
 */

//	Include Bridge File
require plugin_dir_path( __FILE__ ) . 'includes/class-feedme-importer.php';

/**
 *	@description  This function will load the bridge file for the plugin
 *	@param 	    NONE
 *	@return     NONE
 */

function run_feedme() {
    $plugin = new feedme_importer();
}

function feed_me_activate(){
    register_uninstall_hook( __FILE__, 'feed_me_uninstall' );
}

register_activation_hook( __FILE__, 'feed_me_activate' );

function feed_me_uninstall() {
    $delete_data= get_option( 'deleteaction', 'Backup Title');

    if( $delete_data==1 ){
        $allposts= get_posts( array('post_type'=>'feedme_feed','numberposts'=>-1) );
        foreach ($allposts as $eachpost) {
            wp_delete_post( $eachpost->ID, true );
        }

        $allposts= get_posts( array('post_type'=>'feedme_feed_item','numberposts'=>-1) );

        foreach ($allposts as $eachpost) {
            wp_delete_post( $eachpost->ID, true );
        }
    }
}

if (!function_exists('feeds_write_log')) {
    function feeds_write_log($log) {
        $log_action= get_option( 'logaction', 'Backup Title');
        /*$custom_post = array(
          'post_title'    => $log_action,
          'post_type'  => 'main_feeds_write_log',
          'post_status'   => 'draft'
        );
        wp_insert_post( $custom_post );
        */
        if (true === WP_DEBUG) {
            /*$custom_post = array(
              'post_title'    => "ineer",
              'post_type'  => 'main_feeds_write_log',
              'post_status'   => 'draft'
            );
            wp_insert_post( $custom_post );
            */
            if( $log_action==1 ){
                if (is_array($log) || is_object($log)) {
                    error_log(print_r($log, true));
                } else {
                    error_log($log);
                }
            }
        }
    }
}

// Call Importer Function
run_feedme();