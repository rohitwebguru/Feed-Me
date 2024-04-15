<?php
add_action('admin_menu', 'feedme_menu');
function feedme_menu(){
    add_menu_page( 'feed me', 'Feedme', 'manage_options','woo-feedme','feedme_func');
    add_submenu_page('woo-feedme','Feedme', 'Feeds', 'manage_options', 'edit.php?post_type=feedme_feed' );
    add_submenu_page('woo-feedme','Feedme', 'Feed Item', 'manage_options', 'edit.php?post_type=feedme_feed_item');
    add_submenu_page('woo-feedme','Feedme', 'Settings', 'manage_options','woo-feedme', 'feedme_func');
}

function wpdocs_enqueue_feedme_admin_style() {
    wp_register_script( 'my-plugin-script', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js');
    wp_register_style( 'feedme_wp_admin_css', plugins_url( '/assets/style.css', __FILE__ ) , true, '1.0.0' );
    wp_enqueue_style( 'feedme_wp_admin_css' );
}
add_action( 'admin_enqueue_scripts', 'wpdocs_enqueue_feedme_admin_style' );

function force_post_title_init() 
{
  wp_enqueue_script('jquery');
}
add_action('admin_init', 'force_post_title_init');

function force_post_title() {
  echo "<script type='text/javascript'>\n";
  echo "
  jQuery('#publish').click(function(){
        var testervar = jQuery('[id^=\"titlediv\"]')
        .find('#title');
        if (testervar.val().length < 1)
        {
            jQuery('[id^=\"titlediv\"]').css('background', '#F96');
            setTimeout(\"jQuery('#ajax-loading').css('visibility', 'hidden');\", 100);
            swal('Post Title is required');
            setTimeout(\"jQuery('#publish').removeClass('button-primary-disabled');\", 100);
            return false;
        }
    });
  ";
   echo "</script>\n";
}

add_action('edit_form_advanced', 'force_post_title');
// Add this row below to get the same functionality for page creations.
add_action('edit_page_form', 'force_post_title');


function feedme_func(){
    
    $checkbox1=0;
    if(isset($_POST['log'])) {
        $checkbox1=1;
    }
    $checkbox2=0;
    if(isset($_POST['delete'])) {
        $checkbox2=1;
    }
    if(isset($_POST['submit'])){
        add_option( 'logaction', $checkbox1, '', 'yes' );
        add_option( 'deleteaction', $checkbox2, '', 'yes' );    
            if(get_option( 'deleteaction', 'Backup Title')!=''){ 
                update_option( 'logaction', $checkbox1, 'yes' );
                update_option( 'deleteaction', $checkbox2, 'yes' );
        }else{
            add_option( 'logaction', $checkbox1, '', 'yes' );
            add_option( 'deleteaction', $checkbox2, '', 'yes' ); 
        }
        feeds_write_log('Feedme Settings Change Successfully');
    }
    $log_action = get_option( 'logaction', 'Backup Title');

    if( $log_action==1 ){
        $logaction = 1;
    }else{
        $logaction = 0;
    }

    $delete_data= get_option( 'deleteaction', 'Backup Title');  
    if( $delete_data==1 ){
        $deletedata = 1;
    }else{
        $deletedata = 0;
    }
?>


<?php
add_action( 'cmb2_admin_init', 'action_cmb2_admin_init', 10, 1 ); 
    function action_cmb2_admin_init() {
        $cmb_demo = add_field( array(
            'id'           => $prefix . 'metabox',
            'title'        => __( 'Metabox Title', 'cmb2' ),
            'context'      => 'normal',
            'priority'     => 'high',
        ) );
    }
    // exit;    
?>
    <div class="container"><br>
    <form action="" method="post">
    <h2>Feedme settings</h2>
    <h4 style="font-weight: bold;">Log action to debug.log</h4>
    <input style="float: right; margin-right: 79%; margin-top: -33px;" type="checkbox" id="yes" name="log" class="get_value" value="1" <?php if ($logaction == 1) { echo "checked='checked'"; } ?>><br>

    <h4 style="font-weight: bold;">Delete data on plugin deletion</h4>
    <input style="float: right; margin-right: 79%; margin-top: -33px;" type="checkbox" id="yes" name="delete" class="get_value" value="1" <?php if ($deletedata == 1) { echo "checked='checked'"; } ?>><br>

    <input type="submit" value="submit" name="submit" class="button button-primary">
    </form>
    </div>
    <?php
}

function feeds_register_meta_box() {
    add_meta_box( 'rm-meta-box-id', esc_html__( 'Feed Me Url', 'text-domain' ), 'feedme_meta_box_callback', 'post', 'advanced', 'high' );
}
add_action( 'add_meta_boxes', 'feeds_register_meta_box');

function feedme_meta_box_callback( $meta_id ) {

    $outline = '<label for="title_field" style="width:150px; display:inline-block;">'. esc_html__('Feed Me Url', 'text-domain') .'</label>';
    $title_field = get_post_meta( $meta_id->ID, 'title_field', true );
    $outline .= '<input type="text" name="title_field" id="title_field" class="title_field" value="'. esc_attr($title_field) .'" style="width:300px;"/>';

    echo $outline;
}

add_action( 'cmb2_admin_init', 'feedme_register_main_options_metabox' );

function feedme_register_main_options_metabox() {
    $cmb_demo = new_cmb2_box( array(
		'id'            =>'feedme_feed_settings_metabox',
		'title'         => esc_html__( 'Feed Me Feed', 'feedme' ),
		'object_types'  => array( 'feedme_feed' ),
		'priority'   	=> 'low'
	));

    $cmb_demo->add_field( array(
		'name'    => 'Feed URL :',
		'id'      => 'feedme_feed_url',
		'type'    => 'text'

	) );
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
        ),

	) );
    $cmb_demo->add_field( array(
		'name'             => __( 'Feed active :', 'keystone' ),
        'id'               => 'feedme_feed_active',
        'type'             => 'checkbox',
        'sanitization_cb'  => 'sanitize_checkbox',
        'default'          => true,
        'active_value'     => true,
        'inactive_value'   => false

	) );
  
    $cmb_demo->add_field( array(
        'name'           => 'Category  :',
        'id'             => 'feedme_feed_category',
        'taxonomy'       => 'category',
        'type'           => 'taxonomy_select',
        'remove_default' => 'true',
        'options_cb' => 'cmb2_get_post_options',
        'query_args' => array(
        ),
    ) );
    
    $cmb_demo->add_field( array(
		'name'    => 'Next poll :',
		'id'      => 'feedme_feed_nextpoll',
		'type'    => 'text',
        'attributes'  => array(
            'readonly' => 'readonly',
        ),

	) );
    // feeds_write_log('Successfully Insert');
}

?>
