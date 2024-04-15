<?php
class BackgroundAsync extends WP_Background_Process{
    protected $action = 'feed_item_testing';

    protected function task( $feed_item_id ) {
        global $wpdb;
        $feed_item_url = get_post_meta( $feed_item_id, 'feedme_item_url', true);

        if( empty( $feed_item_url )){
            sleep(1);
            return false;
        }

        // error_log( 'Feed Item ID ='.$feed_item_id );
        $feed_item_link = stripslashes($feed_item_url);
        // error_log( 'Feed Item URL ='.$feed_item_link );
        $url=explode('&url=',$feed_item_link);
        $url_new=explode('&',$url[1]);
        $postmeta_table = $wpdb->prefix.'postmeta';

        // Feed_source_url
        $feed_item_query = "SELECT * FROM ".$postmeta_table." where meta_key = 'feed_source_url' && meta_value = '".$url_new[0]."'";
        $is_feed_meta_exist = $wpdb->get_results( $feed_item_query );

        if( empty( $is_feed_meta_exist )){
            //  Create Post from Feed Item
            $this->create_post_from_feed_item( $feed_item_id );
        }

        sleep(1);
        return false;
    }

    public function create_post_from_feed_item( $feed_item_id = '' ) {
	    global $wpdb,$post_title;
	    feeds_write_log('Create Post from the Feed Item');
        $custom_post = array(
          'post_title'    => 'Create Post from the Feed Item',
          'post_type'  => 'feed_inset_feed_async',
          'post_status'   => 'draft'
        );
        wp_insert_post( $custom_post );
		$relationship_table = $wpdb->prefix.'term_relationships';
        $feedme_item_status = get_post_meta( $feed_item_id, 'feedme_item_status', true );
        $category_detail = $wpdb->get_results( 'SELECT * FROM '.$relationship_table.' WHERE object_id="'.$feed_item_id.'"' );

        $category_detail = $wpdb->get_results( 'SELECT * FROM '.$relationship_table.' WHERE object_id="'.$feed_item_id.'"' );

        if( !empty( $category_detail )){
            $category_id = $category_detail[0]->term_taxonomy_id;
        }else{
        	$category_id = 0;
        }

        $feed_status = get_post_meta( $feed_item_id, 'feed_status', true );

        $feedme_item_author = get_post_meta( $feed_item_id, 'feedme_item_author', true );

        if( $feedme_item_status )
        {
        	$feedme_item_url = get_post_meta( $feed_item_id, 'feedme_item_url', true );
            $feed_url_arrray =explode('&url=',$feedme_item_url);
            // feeds_write_log($feed_url_arrray[1]);

            $custom_post = array(
              'post_title'    => $feed_url_arrray[1],
              'post_type'  => 'feed_inset_feed_async',
              'post_status'   => 'draft'
            );
            wp_insert_post( $custom_post );

            if(isset($feed_url_arrray[1]) && $feed_url_arrray[1]!=""){
                $final_feed_url = explode('&',$feed_url_arrray[1]);
                feeds_write_log($final_feed_url[0]);
                /*$custom_post = array(
                  'post_title'    => $final_feed_url[0],
                  'post_type'  => 'feed_inset_feed_async',
                  'post_status'   => 'draft'
                );
                wp_insert_post( $custom_post );
                */
                if(isset($final_feed_url[0]) && !empty($final_feed_url[0])){
                    $resposne = wp_remote_get($final_feed_url[0],array(
                        'timeout' => 20,
                        'User-Agent' => $_SERVER[ 'HTTP_USER_AGENT' ]
                    ));

                    if( is_wp_error( $resposne )){
                        wp_delete_post($feed_item_id);
                        feeds_write_log( 'Post Deleted ='.$final_feed_url[0]);
                        $custom_post = array(
                          'post_title'    => 'Post Deleted ='.$final_feed_url[0],
                          'post_type'  => 'feed_inset_feed_async',
                          'post_status'   => 'draft'
                        );

                        wp_insert_post( $custom_post );
                        sleep(1);
                        return false;
                    }else{
                        $scrp = $resposne[ 'body' ];
                    }

                    if(isset($scrp) && !empty($scrp)){
                        preg_match_all('/<[\s]*meta[\s]*(name|property)="?' . '([^>"]*)"?[\s]*' . 'content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $scrp, $match);
                        $final_array = array_combine($match[2], $match[3]);
                        //  Fetch Post Title
                        $post_title = '';

                        if( isset($final_array[ 'og:title' ]) ){
                            $post_title =  $final_array[ 'og:title' ];
                        }else{
                            $post_title = isset($final_array[ 'title' ])?$final_array[ 'title' ]:'';
                        }

                        //  Fetch Post Description
                        $post_description = '';

                        if( isset($final_array[ 'og:description' ]) ){
                            $post_description =  $final_array[ 'og:description' ];
                        }else{
                            $post_description = isset($final_array[ 'description' ])?$final_array[ 'description' ]:'';
                        }

                        //  Get Feed Item Author ID
                        $post_author_id = get_post_field( 'post_author', $feed_item_id );

                        // Get Feed Id
                        $feed_id = get_post_field( 'feed_id', $feed_item_id );

                        //  Get Feed Status
                        $feed_status = get_post_field( 'feed_status', $feed_id );
                        // $feed_status = get_option( 'feed_status');

                        error_log( 'Feed Status ='.$feedme_item_status);
                        error_log( 'Feed Author ='.$feedme_item_author);
                        error_log( 'Feed ID ='.$feed_id );

                        // Initilize Array For Post
                        $my_post = array(
                            'post_title'    => $post_title,
                            'post_content'  => addslashes($post_description),
                            'post_status'   => $feedme_item_status,
                            'post_author'   => ($feedme_item_author) ? $feedme_item_author : 1,
                            'post_category' => array( $category_id )
                        );

                        // echo '<pre>'; print_r( $my_post ); exit;

                        $post_id = wp_insert_post( $my_post );
                        add_post_meta( $post_id,'feed_source_url',addslashes($final_feed_url[0]) );

                        //  Fetch Site Name
                        $site_name = '';
                        if( isset($final_array[ 'og:site_name' ]) ){
                            $site_name =  $final_array[ 'og:site_name' ];
                        }else{
                            $site_name = isset($final_array[ 'sitename' ])?$final_array[ 'sitename' ]:'';
                        }

                        add_post_meta( $post_id,'site_name',$site_name);

                        $image_resposne = ( isset( $final_array[ 'og:image' ] ))?wp_remote_get($final_array[ 'og:image' ]):0;

                        if( !empty( $image_resposne ) && !is_wp_error($image_resposne ) ){
                            $image_url = $final_array[ 'og:image' ];

                            //$allow = ['gif', 'jpg', 'png','svg'];
                            add_post_meta( $post_id,'feed_image_url',$image_url);
                            $image_name       = rand(1,99999).'_'.time().'.png';
                            $upload_dir       = wp_upload_dir();
                            $resposne = wp_remote_get($image_url,array(
                                'timeout' => 20,
                                'User-Agent' => $_SERVER[ 'HTTP_USER_AGENT' ]
                            ));

                            if( !is_wp_error( $resposne )){
                                $image_data       = $resposne[ 'body' ];
                                $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
                                $filename         = basename( $unique_file_name );

                                // Check folder permission and define file location
                                if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents( $file, $image_data );

                                // Check image file type
                                $wp_filetype = wp_check_filetype( $filename, null );

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name( $filename ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                                // Assign metadata to attachment
                                wp_update_attachment_metadata( $attach_id, $attach_data );

                                // And finally assign featured image to post
                                set_post_thumbnail( $post_id, $attach_id );
                            }
                        }

                        //  Update Feed Item Status
                        update_post_meta( $feed_item_id, 'feedme_item_status','In Process');

                        // Delete Feed Item Post
                        wp_delete_post($feed_item_id);
                    }
                }
            }
            else{
                $scrp= get_contents($url[0]);

                if(isset($scrp) && !empty($scrp)){
                    foreach ($scrp as $key => $value) {
                        add_post_meta( $feed_list_item->ID,$key,$value ,true);
                    }
                }
            }
	    }
	}
}