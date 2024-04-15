<?php
function post_type_feedme_feed_item(){
    $labels = array(
        'name'                  => _x( 'Feeds Item', 'Post type general name', 'feedme' ),
        'singular_name'         => _x( 'Feed Item', 'Post type singular name', 'feedme' ),
        'menu_name'             => _x( 'Feed Item', 'Admin Menu text', 'feedme' ),
        'add_new'               => __( 'Add New feed', 'feedme' ),
        'add_new_item'          => __( 'Add New feed', 'feedme' ),
        'new_item'              => __( 'New feed', 'feedme' ),
        'edit_item'             => __( 'Edit feed', 'feedme' ),
        'view_item'             => __( 'View feed', 'feedme' ),
        'all_items'             => __( 'All feed', 'feedme' ),
        'search_items'          => __( 'Search feed', 'feedme' ),
        'parent_item_colon'     => __( 'Parent feed:', 'feedme' ),
        'not_found'             => __( 'No feed item found.', 'feedme' ),
        'not_found_in_trash'    => __( 'No feed item found in Trash.', 'feedme' ),
        'featured_image'        => _x( 'feed Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'feedme' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'feedme' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'feedme' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'feedme' ),
        'archives'              => _x( 'feedme_feed_item archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'feedme' ),
        'insert_into_item'      => _x( 'Insert into feedme_feed_item', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'feedme' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this feedme_feed_item', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'feedme' ),
        'filter_items_list'     => _x( 'Filter feedme_feed_items list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'feedme' ),
        'items_list_navigation' => _x( 'feedme_feed_items list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'feedme' ),
        'items_list'            => _x( 'feedme_feed_items list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'feedme' ),
    );

    $args = array(
        'labels'   => $labels,
        'public'   => false,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=feedme_feed_item',
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'feedme_feed_item' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title','custom-fields' ),
    );

    register_post_type( 'feedme_feed_item', $args );
}

add_action('init', 'post_type_feedme_feed_item',0);
add_filter('manage_feedme_feed_item_posts_columns', 'feeds_item_table_head');

function feeds_item_table_head( $defaults ) {
    $defaults['feedme_item_url']  = 'Item URL';
    $defaults['feedme_item_category']  = 'Category';
    $defaults['feedme_item_status']  = 'Status';
    return $defaults;
}

add_action( 'manage_feedme_feed_item_posts_custom_column', 'feedme_item_table_content_url', 10, 2 );
function feedme_item_table_content_url( $column_name, $post_id ) {
    global $wpdb;

    switch( $column_name ){
        case 'feedme_item_url':
            $feedme_item_url = get_post_meta( $post_id, 'feedme_item_url', true );
            echo '<a href="'.$feedme_item_url.'" target="_blank">'.$feedme_item_url.'</a>';
            break;

        case 'feedme_item_category':
            $relationship_table = $wpdb->prefix.'term_relationships';
            $category_detail = $wpdb->get_results( 'SELECT * FROM '.$relationship_table.' WHERE object_id='.$post_id );

            if( !empty( $category_detail )){
                $category_id = $category_detail[0]->term_taxonomy_id;
                echo get_cat_name( $category_id );
            }

            break;
        case 'feedme_item_status':
                $feedme_item_status = get_post_meta( $post_id, 'feedme_item_status', true );
                echo $feedme_item_status;
                break;
        case 'feedme_feed_active':
            $feed_active = get_post_meta( $post_id, 'feedme_feed_active', true );
            echo (!empty($feed_active))? 'Yes' :'No';
            break;
    }
}

function remove_post_custom_feed_item_fields() {
    remove_meta_box( 'commentstatusdiv', 'feedme_feed_item', 'normal' );
    remove_meta_box( 'commentsdiv', 'feedme_feed_item', 'normal' );
    remove_meta_box( 'postcustom', 'feedme_feed_item', 'normal' );
    remove_meta_box( 'authordiv', 'feedme_feed_item', 'normal' );
    remove_meta_box( 'postexcerpt', 'feedme_feed_item', 'side' );
}

add_action( 'admin_menu' , 'remove_post_custom_feed_item_fields' );

function get_contents($url){
    feeds_write_log($url);
    feeds_write_log("url start");

    if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
	} else {
		$url = str_replace('https://','http://',$url);
	}

    $html = file_get_contents($url);
    $document = new \DOMDocument;
    libxml_use_internal_errors(true);
    $document->loadHTML($html);
    $path= new \DOMXPath($document);
    $xpath = $path;
    $trs = $xpath->query('//tr');
    feeds_write_log('asas 0000888');

    // Image
    $imagenode = preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i',$html, $matches );
    $image =  $matches[ 1 ][ 1 ];

    // Title
    $titlenode = $document->getElementsByTagName('title');
    $title = $titlenode->item(0)->nodeValue;

    // Meta
    $metas = get_meta_tags($url);
    $sitename = (isset($metas['sitename']) ? $metas['sitename'] : '');
    $description = (isset($metas['description']) ? $metas['description'] : '');
    $array = array('title' => $title, 'image' => $image, 'sitename' => $sitename, 'description' => $description, 'url' => $url);
    feeds_write_log(json_encode($array));
    return $array;
}