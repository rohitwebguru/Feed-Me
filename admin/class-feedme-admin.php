<?php
/**
 * @since      1.1.0
 * @package    feedme
 * @subpackage feedme/includes
 * @author     Rohit Sharma
 */
$postArray = json_decode(get_option( 'check_titles'));
$postIds = json_decode(get_option( 'check_ids'));
$all_feed_items = json_decode(get_option('feed_item_queue'));

//update_option( 'feed_item_queue','' );
//echo '<pre>'; print_r( count($all_feed_items )); exit;
class feedme_admin {
	protected $loader,$plugin_name,$version,$feeditemdispatcher;
	public function __construct( $itemdispacth) {
		$this->plugin_name 	= 'feedme';
		$this->version 		= '1.1.0';
		$this->feeditemdispatcher  = $itemdispacth;

		add_action( 'admin_enqueue_scripts', array($this,'feedme_admin_style_scripts' ));
		add_action('admin_menu', array($this,'feedme_menu'));
		add_action('admin_head', array($this,'nerfherder_correct_current_menu'),50);

		add_action('admin_init', array($this,'feedme_post_title_init'));
		add_action('the_content', array($this,'feedme_add_link'));
		add_action('edit_form_advanced', array($this,'feedme_post_title'));
		add_action('edit_page_form', array($this,'feedme_post_title'));
		add_action( 'add_meta_boxes', array($this,'feeds_register_meta_box'));
		add_action( 'cmb2_admin_init', array($this,'feedme_register_main_options_metabox' ));
		add_action( 'save_post', array($this,'feedme_create_feed_items'));
		add_action( 'before_delete_post', array($this,'feedme_delete_feed_item_data'),999,1);
	}

	public function feedme_add_link( $content ){
		global $post;

		//	Get Feed Source Link
		$feed_source_link = get_post_meta( $post->ID, "feed_source_url", true);

		if( $feed_source_link!= ''){
			$content.= '<p>'.esc_html__('Source', 'feedme').':<a href="'.$feed_source_link.'">'.$feed_source_link.'</a></p>';
		}

		return $content;
	}

	public function feedme_delete_feed_item_data( $post_id ){
		$post_item = get_post_type( $post_id);
		if($post_item == 'feedme_feed_item'){
			$feed_item_link = stripslashes(get_post_meta( $post_id, 'feedme_item_url',true));
			$transient_name = md5($feed_item_link);
			delete_transient( $transient_name );
		}
	}

	public function feedme_create_feed_items ( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        $feedme_url = ( isset( $_POST['feedme_url' ]))?$_POST['feedme_url' ]:'';
        if($feedme_url != ""){
        	update_post_meta( $post_id, "feed_source_url", $feedme_url );
        }
        if ( !isset( $_POST[ 'nonce_CMB2phpfeedme_feed_settings_metabox' ] ) ||
                !wp_verify_nonce( $_POST[ 'nonce_CMB2phpfeedme_feed_settings_metabox' ], 'nonce_CMB2phpfeedme_feed_settings_metabox' ) )
            return;

        if ( ! current_user_can( 'edit_posts' ) )
            return;

        // echo '<pre>'; print_r( $this->feeditemdispatcher ); exit;
		// echo '<pre>'; print_r( $_POST ); echo '</pre>'; exit;
		//	Get Feed Source URL
		// $feedme_feed_url = get_post_meta( $post_id, 'feedme_feed_url', true );
		$feedme_feed_url = ( isset( $_POST['feedme_feed_url' ]))?$_POST['feedme_feed_url' ]:'';
		/*
		if( empty( $feedme_feed_url )){
			$feedme_feed_url = ( isset( $_POST['feedme_feed_url' ]))?$_POST['feedme_feed_url' ]:'';
		}
		*/

		if( isset( $_POST[ 'feedme_feed_category' ] ) && !empty($_POST[ 'feedme_feed_category' ])){
			$category_detail = get_category_by_slug( $_POST[ 'feedme_feed_category' ]);
			$category_id = $category_detail->term_id;
		}else{
			$category_id = get_option('default_category');
		}

		$this->create_feed_items_from_url( $feedme_feed_url,$category_id,$post_id );

     }

	public function create_feed_items_from_url( $feedme_post_url = '', $category_id = 0,$feed_Id ){
		// echo '<pre>';print_r($_POST['feed_status']);exit;
		global $wpdb,$post_title;
		$relationship_table = $wpdb->prefix.'term_relationships';
		$sxe = null;
		// echo'<pre>';print_r($_POST['feedme_feed_author']);exit;
		if(!empty($feedme_post_url)){
			$sxe = new SimpleXMLElement( $feedme_post_url, LIBXML_NOCDATA, true );
			// echo "<pre>";print_r($post_Id);exit;
			$postArray = array();
			$postIds = array();
			// echo '<pre>'; print_r( $sxe->entry ); exit;
			foreach($sxe->entry as $index => $value) {
				//echo "<pre>";print_r($value);exit;
				$posttitle = sanitize_title($value->title);
				//	Check source url

				$postid = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $posttitle . "'" );

				// echo'<pre>';print_r($postid);exit;

				if($postid == ''){
					foreach($value->link->attributes() as $tag => $feed_item_link ) {
						$url=explode('&url=',$feed_item_link);
						$url_new=explode('&',$url[1]);
						$postmeta_table = $wpdb->prefix.'postmeta';

						// Feed_source_url
						$feed_item_query = "SELECT * FROM ".$postmeta_table." where meta_key = 'feed_source_url' && meta_value = '".$url_new[0]."'";

						$is_feed_meta_exist = $wpdb->get_results( $feed_item_query );

						//	Get Feed Transient
						$is_feed_item_exist = get_transient(md5($feed_item_link));

						if( empty( $is_feed_item_exist ) && empty( $is_feed_meta_exist )){
							$title = addslashes(strip_tags($value->title));
							$postArray[] = $title;
							$author = ($_POST['feedme_feed_author']) ? $_POST['feedme_feed_author'] : 1;

							if($tag=='href'){
								$my_post = array(
									'post_title'    => $title,
									'post_content'  => '',
									'post_status'   => 'publish',
									'post_author'   => $author,
									'post_type'     => 'feedme_feed_item'
								);

								$post_id = wp_insert_post( $my_post );
								$postIds[] = $post_id;
								$feed_status = (!empty($_POST['feed_status']) ? $_POST['feed_status'] : 'Draft');
								add_post_meta( $post_id, 'feedme_item_url',addslashes($feed_item_link), true);
								add_post_meta( $post_id, 'feedme_item_status',$feed_status, true);
								add_post_meta( $post_id, 'feed_id',$feed_Id, true);
								add_post_meta( $post_id, 'feedme_item_author',$author, true);
								set_transient( md5( $feed_item_link ), '1', 86400);
								$wpdb->insert($relationship_table, array(
									'object_id' =>$post_id,
									'term_taxonomy_id' => $category_id,
									'term_order' => '0'
								));

								$this->feeditemdispatcher->add_feedItem_to_queue( $post_id );
							}
						}
					}
				}

				// echo '<pre>'; print_r( $postArray); print_r($postIds ); exit;
				update_option( 'check_titles',json_encode($postArray) );
				update_option( 'check_ids',json_encode($postIds) );
			}
		}
	}

	public function feedme_register_main_options_metabox() {
	    $cmb_demo = new_cmb2_box( array(
			'id'            =>'feedme_feed_settings_metabox',
			'title'         => esc_html__( 'Feed Me Feed', 'feedme' ),
			'object_types'  => array( 'feedme_feed'),
			'priority'   	=> 'low'
		));

	    $cmb_demo->add_field( array(
			'name'    => 'Feed URL :',
			'id'      => 'feedme_feed_url',
			'type'    => 'text'
		));

		$cmb_demo->add_field( array(
	        'name' 	=> 'Feed Status:',
			'id' 	=> 'feed_status',
			'type' 	=> 'select',
			'show_option_none' => true,
			'options' => array(
				'Draft' => __('Draft', 'cmb2'),
				'Publish' => __('Publish', 'cmb2'),
				'Future' => __('Future', 'cmb2'),
				'Pending' => __('Pending', 'cmb2'),
				'Private' => __('Private', 'cmb2'),
				'Trash' => __('Trash', 'cmb2'),
				'Auto-Draft' => __('Auto-Draft', 'cmb2'),
				'Inherit' => __('Inherit', 'cmb2'),
			),
	    ));

	    $cmb_demo->add_field( array(
			'name'    => 'Poll frequency (mins) :',
			'id'      => 'feedme_feed_poll_frequency',
			'type'    => 'text',
	        'default' => '60',
	        'attributes' => array(
	            'type' => 'number',
	            'min' => '1',
	            'max' => '1440',
	            'step' => '1',
	        )
		));

	    $cmb_demo->add_field( array(
			'name'             => __( 'Feed active :', 'keystone' ),
	        'id'               => 'feedme_feed_active',
	        'type'             => 'checkbox',
	        'sanitization_cb'  => 'sanitize_checkbox',
	        'default'          => true,
	        'active_value'     => true,
	        'inactive_value'   => false
		));

	    $cmb_demo->add_field( array(
	        'name'           => 'Category :',
	        'id'             => 'feedme_feed_category',
	        'taxonomy'       => 'category',
	        'type'           => 'taxonomy_select',
	        'remove_default' => 'true',
	        'options_cb' => 'cmb2_get_post_options',
	        'query_args' => array(
	        )
	    ));

	    $cmb_demo->add_field( array(
			'name'    => 'Next poll :',
			'id'      => 'feedme_feed_nextpoll',
			'type'    => 'hidden',
	        'attributes'  => array(
	            'readonly' => 'readonly',
	        )
		));

		$cmb_demo->add_field( array(
	        'name'           	=> 'Feed Author:',
	        'id'             	=> 'feedme_feed_author',
			'show_option_none' 	=> true,
	        'type'           	=> 'select',
			'options' 		 	=> $this->cmb2_get_user_options( array( 'fields' => array( 'user_login' ) ) ),
	    ));
	}

	public function cmb2_get_user_options( $query_args ) {

		$args = wp_parse_args( $query_args, array(

			'fields' => array( 'user_login' ),

		) );

		$users = get_users(  );

		$user_options = array();
		if ( $users ) {
			foreach ( $users as $user ) {
			  $user_options[ $user->ID ] = $user->user_login;
			}
		}

		return $user_options;
	}

	public function feeds_register_meta_box() {
		add_meta_box( 'rm-meta-box-id', esc_html__( 'Feed Url', 'feedme' ), array($this,'feedme_meta_box_callback'), 'post', 'advanced', 'high' );
	}

	public function feedme_meta_box_callback( $meta_id ) {
	    $outline = '<label for="feed_source_url" style="width:150px; display:inline-block;">'. esc_html__('Feed URL', 'feedme') .'</label>';
	    $feed_source_url = get_post_meta( $meta_id->ID, 'feed_source_url', true );
	    $outline .= '<input type="text" name="feedme_url" id="feedme_url" class="title_field" value="'. esc_attr($feed_source_url) .'" style="width:300px;"/>';
	    echo $outline;
	}

	public function feedme_post_title(){
	  echo "<script type='text/javascript'>\n";
	  echo "
	  jQuery('#publish').click(function(){
	        var testervar = jQuery('[id^=\"titlediv\"]')
	        .find('#title');
	        if (testervar.val().length < 1)
	        {
	            jQuery('[id^=\"titlediv\"]').css('background', '#F96');
	            setTimeout(\"jQuery('#ajax-loading').css('visibility', 'hidden');\", 100);
	            swal.fire('Post Title is required');
	            setTimeout(\"jQuery('#publish').removeClass('button-primary-dfeedsbled');\", 100);
	            return false;
	        }
	    });
	  ";

	   echo "</script>\n";
	}

	public function feedme_post_title_init(){
		wp_enqueue_script('jquery');
	}

	public function feedme_admin_style_scripts() {
	    wp_enqueue_script( 'feedme_sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@9', array ( 'jquery' ), '1.0.0' );
	    wp_enqueue_script( 'feedme_wp_admin_js', plugins_url( '../admin/assets/feeds.js', __FILE__ ) , array ( 'jquery' ),'1.0.0' );
	    wp_register_style( 'feedme_wp_admin_css', plugins_url( '../admin/assets/style.css', __FILE__ ) , true, '1.0.0' );
	    wp_enqueue_style( 'feedme_wp_admin_css' );
	}

	public function feedme_menu(){
	    add_menu_page( 'feedme', 'Feedme', 'manage_options','edit.php?post_type=feedme_feed'); // array($this,'feedme_func')
	    add_submenu_page('edit.php?post_type=feedme_feed','Feedme', 'All Feeds', 'manage_options', 'edit.php?post_type=feedme_feed' );
	    add_submenu_page('edit.php?post_type=feedme_feed','Feedme', 'Add New Feed', 'manage_options', 'post-new.php?post_type=feedme_feed' );
	    add_submenu_page('edit.php?post_type=feedme_feed','Feedme', 'Feed Item', 'manage_options', 'edit.php?post_type=feedme_feed_item');
	    add_submenu_page('edit.php?post_type=feedme_feed','Feedme', 'Settings', 'manage_options','woo-feedme', array($this,'feedme_func'));
		remove_submenu_page( 'edit.php?post_type=feedme_feed', 'edit.php?post_type=feedme_feed' );

	    //add_menu_page('Members', 'Members', 'manage_options', 'members-slug', 'members_function');
		//add_submenu_page( 'members-slug', 'Add Members', 'Add Members', 'manage_options', 'add-members-slug', 'add_members_function');
	}

	public function nerfherder_correct_current_menu(){
	$screen = get_current_screen();
	if ( $screen->id == 'feedme_feed' || $screen->id =='edit-feedme_feed_item' || $screen->id == 'edit-feedme_feed' ) {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#toplevel_page_woo-feedme').addClass('wp-has-current-submenu wp-menu-open menu-top menu-top-first').removeClass('wp-not-current-submenu');
		$('#toplevel_page_woo-feedme > a').addClass('wp-has-current-submenu').removeClass('wp-not-current-submenu');
	});
	</script>
	<?php
	}

	if ( $screen->id == 'feedme_feed' ) {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('a[href$="post-new.php?post_type=feedme_feed"]').parent().addClass('current');
		$('a[href$="post-new.php?post_type=feedme_feed"]').addClass('current');

		$('a[href$="edit.php?post_type=feedme_feed"]').parent().removeClass('current');
		$('a[href$="edit.php?post_type=feedme_feed"]').removeClass('current');

		$('#toplevel_page_woo-feedme .wp-first-item').attr("style","display:none;")
		//$('a[href$="admin.php?page=woo-feedme"]').parent().hide();
	});


	</script>
	<?php
	}

	if ( $screen->id == 'edit-feedme_feed_item' ) {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#toplevel_page_woo-feedme .wp-first-item').attr("style","display:none;")
	});
	</script>
	<?php
	}
	if ( $screen->id == 'edit-feedme_feed' ) {
	?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#toplevel_page_woo-feedme .wp-first-item').attr("style","display:none;")
			});
			</script>
	<?php
	}
}



	public function feedme_func(){
		global $feeditemdispatcher;
		$existing_feed_items = json_decode(get_option( 'feed_item_queue' ));

	    if(isset($_POST['submit'])){
			if( isset($_POST[ 'delete' ]) ){
				update_option( 'deleteaction', 1 );
			}else{
				update_option( 'deleteaction', '' );
			}

			if( isset($_POST[ 'log' ]) ){
				update_option( 'logaction', 1 );
			}else{
				update_option( 'logaction', '' );
			}

			if( isset($_POST[ 'feed_status' ]) ){
				update_option( 'feed_status', $_POST[ 'feed_status' ] );
			}else{
				update_option( 'feed_status', '' );
			}

			feeds_write_log('Feedme Settings Change Successfully');
	    }

	    $log_action = get_option( 'logaction' );

	    if( $log_action==1 ){
	        $logaction = 1;
	    }else{
	        $logaction = 0;
	    }

	    $delete_data= get_option( 'deleteaction' );

	    if( $delete_data==1 ){
	        $deletedata = 1;
	    }else{
	        $deletedata = 0;
	    }

	    // $feed_status= get_option( 'feed_status' );
	?>
	    <div class="container"><br>
	    <h2 style="font-weight: bold;">Feedme Settings</h2>
	    <form action="#" method="post">
	    <!-- <h2>Feedme settings</h2> -->
	    <h4 style="font-weight: bold;">Log action to debug.log</h4>
	    <input style="float: right; margin-right: 79%; margin-top: -33px;" type="checkbox" id="yes" name="log" class="get_value" value="1" <?php if ($logaction == 1) { echo "checked='checked'"; } ?>><br>
	    <h4 style="font-weight: bold;">Delete data on plugin deletion</h4>
	    <input style="float: right; margin-right: 79%; margin-top: -33px;" type="checkbox" id="yes" name="delete" class="get_value" value="1" <?php if ($deletedata == 1) { echo "checked='checked'"; } ?>><br>
	    <br>
	    <input type="submit" value="submit" name="submit" class="button button-primary">
	    </form>
	    </div>
	    <?php
	}
}

global $feedme_admin_obj;
$backgroundAsync = new BackgroundAsync();
// $backgroundAsync->create_post_from_feed_item( 841 ); exit;

$feeditemdispatcher = new FeedItemDispatcher($backgroundAsync);
$feedme_admin_obj = new feedme_admin( $feeditemdispatcher );

/* Cron Schedules */
add_filter( 'cron_schedules', 'feeds_add_every_five_minutes' );

function feeds_add_every_five_minutes( $schedules ) {
    $schedules['every_five_minutes'] = array(
        'interval'  => 5*60,
        'display'   => __( 'Every 5 Minutes', 'textdomain' )
    );

    return $schedules;
}

if ( ! wp_next_scheduled( 'feeds_add_every_five_minutes' ) ) {
    wp_schedule_event( time(), 'every_five_minutes', 'feeds_add_every_five_minutes' );
}

add_action( 'feeds_add_every_five_minutes', 'every_five_minutes_event_func' );

function every_five_minutes_event_func() {
	global $wpdb,$post_title,$feeditemdispatcher,$feedme_admin_obj;
    feeds_write_log('cron function success');
    $custom_post = array(
      'post_title'    => "cron function success",
      'post_type'  => 'feed_inset_class_feedme_admin',
      'post_status'   => 'draft'
    );
    wp_insert_post( $custom_post );
	$list_feedme_feed = get_posts(array('post_type' => 'feedme_feed','meta_query' => array(
        array(
            'key'       => 'feedme_feed_active',
            'value'     => 'on',
        )
    )));

	foreach ($list_feedme_feed as $feed_list) {
		$feedme_post_url = get_post_meta( $feed_list->ID, 'feedme_feed_url', true );
		$feedme_feed_nextpoll = get_post_meta( $feed_list->ID, 'feedme_feed_nextpoll', true);
		$relationship_table = $wpdb->prefix.'term_relationships';
		$category_id = 0;
        $category_detail = $wpdb->get_results( 'SELECT * FROM ' . $relationship_table.' WHERE object_id="'.$feed_list->ID.'"' );

		if( !empty( $category_detail )){
			$category_id = $category_detail[0]->term_taxonomy_id;
		}

		feeds_write_log('cron cc '.$category_id);
		$custom_post = array(
	      'post_title'    => 'cron cc '.$category_id,
	      'post_type'  => 'feed_inset_class_feedme_admin',
	      'post_status'   => 'draft'
	    );
	    wp_insert_post( $custom_post );
	    $ctime=strtotime(date('Y-m-d H:i'));
		feeds_write_log(date('Y-m-d H:i',$feedme_feed_nextpoll).'&&&&'.date('Y-m-d H:i').'&&&'.date_default_timezone_get());
		$custom_post = array(
	      'post_title'    => date('Y-m-d H:i',$feedme_feed_nextpoll).'&&&&'.date('Y-m-d H:i').'&&&'.date_default_timezone_get(),
	      'post_type'  => 'feed_inset_class_feedme_admin',
	      'post_status'   => 'draft'
	    );
	    wp_insert_post( $custom_post );
		if($feedme_feed_nextpoll<=$ctime){
			$feed_active = get_post_meta( $feed_list->ID, 'feedme_feed_poll_frequency', true );
			feeds_write_log('cron cfeed_activec '.$feed_active);
			$custom_post = array(
		      'post_title'    => 'cron cfeed_activec '.$feed_active,
		      'post_type'  => 'feed_inset_class_feedme_admin',
		      'post_status'   => 'draft'
		    );
		    wp_insert_post( $custom_post );
			$feed_active_new=date('Y-m-d H:i',strtotime('+'.$feed_active.'minutes ',$feedme_feed_nextpoll));
			feeds_write_log('cron feed_active_new www '.$feed_active_new);
			$custom_post = array(
		      'post_title'    => 'cron feed_active_new www '.$feed_active_new,
		      'post_type'  => 'feed_inset_class_feedme_admin',
		      'post_status'   => 'draft'
		    );
		    wp_insert_post( $custom_post );
			update_post_meta( $feed_list->ID, 'feedme_feed_nextpoll',strtotime($feed_active_new));
			//	Create Feed Items
			$feedme_admin_obj->create_feed_items_from_url( $feedme_post_url,$category_id,$feed_list->ID);
		}
	}
}
