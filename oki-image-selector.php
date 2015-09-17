<?php
/*
Plugin Name: Oki Image Selector
Version: 0.1
Author: Okinet Sp. z o.o.
Author URI: http://okinet.pl
*/

define('ITEM_TEMPLATE', '<div class="item" id="item_{ITEM_ID}"><span class="del-item"></span><img src="{ITEM_URL}" />{ITEM_TITLE}</div>');


function ois_scripts_basic()
{
    // Register scripts and styles for a plugin:
    wp_enqueue_script( 'ois_script', plugin_dir_url( __FILE__ ) . 'js/ois.js', array('jquery', 'jquery-ui-sortable') );
    wp_enqueue_style( 'ois_style', plugin_dir_url( __FILE__ ) . 'css/ois.css');


}
add_action('admin_print_scripts', 'ois_scripts_basic');

/**
 * Registers new meta box
 */
function ois_add_meta_box() {

    $screens = array( 'post', 'page' );

    foreach ( $screens as $screen ) {

        add_meta_box(
            'myplugin_sectionid',
            __( 'Powiązane pliki', 'ois' ),
            'ois_meta_box_callback',
            $screen,
            'normal',
            'high'
        );
    }
}
add_action( 'add_meta_boxes', 'ois_add_meta_box' );


/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function ois_meta_box_callback( $post ) {

    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'myplugin_save_meta_box_data', 'myplugin_meta_box_nonce' );

    inject_js_variables();

    echo '<button id="image_button_id" name="image_button_id" class="button upload_image_button">'.__('Select files', 'ois').'</button>';

    echo '<div id="related-files-container">';

    $ids = array();

    foreach(getRelatedFiles($post->ID) as $file) {
        $replacements = array($file->ID, $file->post_title);
        if(strpos($file->post_mime_type, 'image') === false)
            $replacements[] = site_url().'/wp-includes/images/media/default.png';
        else
            $replacements[] = $file->guid;

        echo str_replace(array('{ITEM_ID}','{ITEM_TITLE}', '{ITEM_URL}'), $replacements, ITEM_TEMPLATE);

        $ids[] = $file->ID;
    }

    echo '</div><div class="clearfix">&nbsp;</div>
          <input type="hidden" value="'.implode(',', $ids).'" name="ois_related_files" id="ois_value" />';

}

/**
 * Return an array with files
 * @param $post_id
 * @param null $file_type
 * @return array
 */
function getRelatedFiles($post_id, $file_type = null)
{
    $value = get_post_meta( $post_id, '_related_files', true );
    $files = explode(',', $value);
    $list = array();
    foreach($files as $file) {
        if((int)$file == 0) continue;
        $post = get_post($file);
        if($file_type != null && strpos($post->post_mime_type, $file_type) !== false)
            $list[] = $post;
        else
            $list[] = $post;
    }
    return $list;
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function ois_save_meta_box_data( $post_id ) {

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }

    } else {

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // Make sure that it is set.
    if ( ! isset( $_POST['ois_related_files'] ) ) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field( $_POST['ois_related_files'] );
    $tab = explode(',', $my_data);
    $final = array();
    foreach($tab as $item) {
        $final[] = str_replace('item_', '', $item);
    }
    // Update the meta field in the database.
    update_post_meta( $post_id, '_related_files', implode(',', $final));
}
add_action( 'save_post', 'ois_save_meta_box_data' );

function inject_js_variables()
{
    echo '<script type="text/javascript">
            var item_template = \''.ITEM_TEMPLATE.'\';
            var ois_trans = {
                "assign_files": \''.__('Assign files', 'ois').'\',
                "select_files": \''.__('Select files', 'ois').'\'
            };
          </script>';
}