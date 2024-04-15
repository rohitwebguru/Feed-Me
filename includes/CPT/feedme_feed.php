<?php
function post_type_feedme_feed(){
    $labels = array(
        'name'                  => _x( 'Feeds', 'Post type general name', 'feedme' ),
        'singular_name'         => _x( 'Post', 'Post type singular name', 'feedme' ),
        'menu_name'             => _x( 'Feedme', 'Admin Menu text', 'feedme' ),
        'add_new'               => __( 'Add New', 'feedme' ),
        'add_new_item'          => __( 'Add New', 'feedme' ),
        'new_item'              => __( 'New Post', 'feedme' ),
        'edit_item'             => __( 'Edit Post', 'feedme' ),
        'view_item'             => __( 'View Post', 'feedme' ),
        'all_items'             => __( 'Feedme', 'feedme' ),
        'search_items'          => __( 'Search Post', 'feedme' ),
        'parent_item_colon'     => __( 'Parent Post:', 'feedme' ),
        'not_found'             => __( 'No Feed found.', 'feedme' ),
        'not_found_in_trash'    => __( 'No Feed found in Trash.', 'feedme' ),
        'featured_image'        => _x( 'Po Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'feedme' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'feedme' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'feedme' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'feedme' ),
        'archives'              => _x( 'feedme_feed archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'feedme' ),
        'insert_into_item'      => _x( 'Insert into feedme_feed', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'feedme' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this feedme_feed', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'feedme' ),
        'filter_items_list'     => _x( 'Filter feedme_feeds list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'feedme' ),
        'items_list_navigation' => _x( 'feedme_feeds list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'feedme' ),
        'items_list'            => _x( 	'feedme_feeds list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'feedme' ),
    );    

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'menu_icon'          => 'dashicons-text-page',
        'show_in_menu'       => 'edit.php?post_type=feedme_feed',
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'feedme_feed' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'custom-fields'),
    );

    register_post_type( 'feedme_feed', $args );
}

add_action('init', 'post_type_feedme_feed',0);
add_filter('manage_feedme_feed_posts_columns', 'feeds_table');

function feeds_table( $defaults ) {
    $defaults['feedme_feed_url']  = 'Feed URL';
    $defaults['feedme_feed_poll_frequency']  = 'Poll frequency';
    $defaults['feedme_feed_feed_status']  = 'Feed Status';
    $defaults['feedme_feed_active']  = 'Active';
    $defaults['feedme_feed_category']  = 'Category';
    $defaults['feedme_feed_nextpoll']  = 'Next poll';
    $defaults['author']  = 'Author';
    return $defaults;
}

add_action( 'manage_feedme_feed_posts_custom_column', 'feeds_table_content_url', 10, 2 );

function feeds_table_content_url( $column_name, $post_id ) {
    global $wpdb;

    switch( $column_name ){        
        case 'feedme_feed_url':
            $feedme_post_url = get_post_meta( $post_id, 'feedme_feed_url', true );
            echo '<a href="'.$feedme_post_url.'" target="_blank">'.$feedme_post_url.'</a>';        
            break;
        case 'feedme_feed_poll_frequency':
            $poll_frequency = get_post_meta( $post_id, 'feedme_feed_poll_frequency', true );
            echo $poll_frequency;
            break;
        case 'feedme_feed_feed_status':
            $feed_status = get_post_meta( $post_id, 'feed_status', true );
            echo $feed_status;
            break;
        case 'feedme_feed_active':
            $feed_active = get_post_meta( $post_id, 'feedme_feed_active', true );
            echo (!empty($feed_active))? 'Yes' :'No';
            break;
        case 'feedme_feed_category':
            $relationship_table = $wpdb->prefix.'term_relationships';
            $category_detail = $wpdb->get_results( 'SELECT * FROM '.$relationship_table.' WHERE object_id="'.$post_id.'"' );

            if( !empty( $category_detail )){
                $category_id = $category_detail[0]->term_taxonomy_id;
                echo get_cat_name( $category_id );                
            }

            break;
        case 'feedme_feed_nextpoll':
            $feed_active = get_post_meta( $post_id, 'feedme_feed_nextpoll', true );

            if($feed_active!=""){
                $feed_active=date('d-m-Y H:i',$feed_active);
            }

            echo $feed_active;
            //echo '<br>'.date('d-m-Y H:i:s');
            break;            
    }            
}

function remove_post_custom__feeds_fields() {
    remove_meta_box( 'commentstatusdiv', 'feedme_feed', 'normal' );
    remove_meta_box( 'commentsdiv', 'feedme_feed', 'normal' );
    remove_meta_box( 'postcustom', 'feedme_feed', 'normal' );
    remove_meta_box( 'authordiv', 'feedme_feed', 'normal' );
    remove_meta_box( 'postexcerpt', 'feedme_feed', 'side' );
}

add_action( 'admin_menu' , 'remove_post_custom__feeds_fields' );