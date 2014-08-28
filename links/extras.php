<?php // Stuff to work on or add at a later date

/*
 * 1. Do not publish post if there is no link or title
 * 2. Add settings page 
      a. Change permalinks
      b. Update left sidebar menu titles
 * 3. Add custom caps
*/

public function add_content_type() {
    $args = array(
        'map_meta_cap'        => true,
        'capability_type'     => 'link',
        'capabilities'        => array(
            'publish_posts'         => 'publish_links',
            'edit_posts'            => 'edit_links',
            'edit_others_posts'     => 'edit_others_links',
            'delete_posts'          => 'delete_links',
            'delete_others_posts'   => 'delete_links',
            'edit_post'             => 'edit_link',
            'delete_post'           => 'delete_link',
            'read_post'             => 'read_link'
        )
    );
}

/* User Capabilities */
add_filter( 'map_meta_cap', array( $this, 'my_map_meta_cap' ), 10, 4 );
function my_map_meta_cap( $caps, $cap, $user_id, $args ) {

    /* If editing, deleting, or reading a link, get the post and post type object. */
    if ( 'edit_link' == $cap || 'delete_link' == $cap || 'read_link' == $cap ) {
        $post = get_post( $args[0] );
        $post_type = get_post_type_object( $post->post_type );

        /* Set an empty array for the caps. */
        $caps = array();
    }

    /* If editing a link, assign the required capability. */
    if ( 'edit_link' == $cap ) {
        if ( $user_id == $post->post_author )
            $caps[] = $post_type->cap->edit_posts;
        else
            $caps[] = $post_type->cap->edit_others_posts;
    }

    /* If deleting a link, assign the required capability. */
    elseif ( 'delete_link' == $cap ) {
        if ( $user_id == $post->post_author )
            $caps[] = $post_type->cap->delete_posts;
        else
            $caps[] = $post_type->cap->delete_others_posts;
    }

    /* If reading a private link, assign the required capability. */
    elseif ( 'read_link' == $cap ) {

        if ( 'private' != $post->post_status )
            $caps[] = 'read';
        elseif ( $user_id == $post->post_author )
            $caps[] = 'read';
        else
            $caps[] = $post_type->cap->read_private_posts;
    }

    /* Return the capabilities required by the user. */
    return $caps;
}

remove_meta_box( 'submitdiv', 'my_links', 'side' );

add_meta_box(
    'my_links_submit',
    __( 'Post', self::slug ),
    array( $this, 'my_links_submit_meta_box_content' ),
    self::slug,
    'side',
    'default'
);

/* Add the actual fields to the custom meta box */
public function my_links_submit_meta_box_content( $post ) {
    //global $post;

    // Add an nonce field so we can check for it later.
    wp_nonce_field( plugin_basename( __FILE__ ), 'my_links_submit_meta_box_content_nonce' );

    $url = get_post_meta( $post->ID, '_link-url', true );

    ?>
    <div class="submitbox" id="submitlink">
        <div id="minor-publishing" style="margin-bottom:15px;">
            <?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
            <div style="display:none;">
                <?php submit_button( __( 'Save' ), 'button', 'save', false ); ?>
            </div>

            <div id="minor-publishing-actions">
                <div id="preview-action">
                <?php if ( !empty($url) ) { ?>
                    <a class="preview button" href="<?php echo $url; ?>" target="_blank"><?php _e('Visit Link'); ?></a>
                <?php } ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>

        <div id="major-publishing-actions">
            <?php
            /** This action is documented in wp-admin/includes/meta-boxes.php */
            do_action( 'post_submitbox_start' );
            ?>
            <div id="delete-action">
                <?php
            if ( !empty($_GET['action']) && 'edit' == $_GET['action'] && current_user_can('edit_posts') ) { ?>
                <a class="submitdelete deletion" href="<?php echo wp_nonce_url("post.php?post=$post->ID&amp;action=delete", 'delete-post_' .  $post->ID); ?>" onclick="if ( confirm('<?php echo esc_js(sprintf(__("You are about to delete this link '%s'\n 'Cancel' to stop, 'OK' to delete."), $post->post_name )); ?>') ) {return true;}return false;"><?php _e('Delete'); ?></a>
            <?php } ?>
            </div>

            <div id="publishing-action">
                <?php if ( !empty($url) ) { ?>
                  <input name="save" type="submit" class="button-large button-primary" id="publish" accesskey="p" value="<?php esc_attr_e('Update Link') ?>" />
                <?php } else { ?>
                    <input name="save" type="submit" class="button-large button-primary" id="publish" accesskey="p" value="<?php esc_attr_e('Add Link') ?>" />
                <?php } ?>
            </div>
            <div class="clear"></div>
        </div>
        <?php
        /**
         * Fires at the end of the Publish box in the Link editing screen.
         *
         * @since 2.5.0
         */
        do_action( 'submitlink_box' );
        ?>
        <div class="clear"></div>
    </div>
    <?php
}