<?php
/*
Plugin Name: Link Content Type
Plugin URI:
Description: Add a link content type to your WordPress site.
Version: 0.1
Author: Jessica Lavoie
Author Email: jessical@hallme.com
License:

Copyright 2014 Jessica Lavoie (jessical@hallme.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class My_Links {

	/*--------------------------------------------*
	 * Variables and Constants
	 *--------------------------------------------*/
	const name = 'My Links';
    const singular = 'My Link';
	const slug = 'my_links';
	protected static $instance = null;

	static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/* Constructor */
	function __construct() {
        
		$this->init_links_content_type();
        
	}

	/* Runs when the plugin is activated */
	function install_links_content_type() {}

	/* Runs when the plugin is initialized */
	function init_links_content_type() {
        
        add_action( 'init', array( $this, 'add_content_type' ) );
        add_action( 'init', array( $this, 'add_taxonomies' ) );
        add_filter( 'rewrite_rules_array', array( $this, 'my_links_add_rewrite_rules' ) );
        add_filter( 'post_type_link', array( $this, 'my_links_filter_post_type_link' ), 10, 2 );
        add_shortcode( 'press_links', array( $this, 'press_links_shortcode' ) );
        add_filter( 'template_include',  array( $this, 'include_template_function' ), 1 );
        add_filter( 'pre_get_posts', array( $this, 'my_links_search_filter' ) );

		if ( is_admin() ) {
            
			add_filter( 'post_updated_messages', array( $this, 'update_messages' ) );
			add_action( 'contextual_help', array( $this, 'update_contextual_help' ), 10, 3 );
			add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_custom_meta_box' ) );
            add_action( 'add_meta_boxes', array( $this, 'my_link_remove_metaboxes' ), 999 );
            add_action( 'admin_menu', array( $this, 'add_custom_meta_box' ) );
            add_action( 'save_post', array( $this, 'my_links_category_set_default_object_terms' ), 100, 2 );
            
            add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_admin_style' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'meta_box_admin_script' ) );

		}
        
	}

	/* Add the custom content type */
	public function add_content_type() {
		$labels = array(
			'name'               => _x( self::name, 'post type general name' ),
			'singular_name'      => _x( self::name, 'post type singular name' ),
			'add_new'            => __( 'Add New' ),
			'add_new_item'       => __( 'Add New Link' ),
			'edit_item'          => __( 'Edit Link' ),
			'new_item'           => __( 'New Link' ),
			'all_items'          => __( 'All '.self::name ),
			'view_item'          => __( 'View '.self::name ),
			'search_items'       => __( 'Search My Links' ),
			'not_found'          => __( 'No Links found' ),
			'not_found_in_trash' => __( 'No Links found in the Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => self::name
		);
		$args = array(
			'labels'        	  => $labels,
			'description'   	  => 'Holds our '.self::slug.'s and '.self::slug.' specific data',
			'public'        	  => false,
            'publicly_queryable'  => false,
			'exclude_from_search' => false,
            'hierarchical'        => false,
			'show_ui'             => true,
            'query_var'           => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position' 	  => 5,
			'menu_icon' 		  => 'dashicons-admin-links',
			'can_export'          => true,
			'supports'      	  => array( 'title' ),
            'taxonomies'          => array( 'my_links_category' ),
            'has_archive'         => false,
            'rewrite'	          => array( 'slug' => 'press-room/%my_links_category%', 'with_front' => true ),
            'capability_type'     => 'post',
		);
		register_post_type( self::slug, $args );
	}

	/* Add taxonomies for the new content type */
	public function add_taxonomies() {
		$labels = array(
			'name'                => _x( self::singular.' Categories', 'taxonomy general name' ),
			'singular_name'       => _x( self::singular.' Category', 'taxonomy singular name' ),
			'search_items'        => __( 'Search '.self::singular.' Categories' ),
			'all_items'           => __( 'All '.self::singular.' Categories' ),
			'parent_item'         => __( 'Parent '.self::singular.' Category' ),
			'parent_item_colon'   => __( 'Parent '.self::singular.' Category:' ),
			'edit_item'           => __( 'Edit '.self::singular.' Category' ),
			'update_item'         => __( 'Update '.self::singular.' Category' ),
			'add_new_item'        => __( 'Add New '.self::singular.' Category' ),
			'new_item_name'       => __( 'New '.self::singular.' Category' ),
			'menu_name'           => __( self::singular.' Categories' )
		);
		$args = array(
			'labels'              => $labels,
			'hierarchical'        => true,
            'rewrite'             => array( 'slug' => 'press-room' )
		);
		register_taxonomy( self::slug.'_category', self::slug, $args );
	}
    
    /* Permastructure */
    public function my_links_add_rewrite_rules( $rules ) {
      $new = array();
      $new['press-room/([^/]+)/(.+)/?$'] = 'index.php?my_links=$matches[2]';
      $new['press-room/(.+)/?$'] = 'index.php?my_links_category=$matches[1]';

      return array_merge( $new, $rules ); // Ensure our rules come first
    }

    /* Handle the '%my_links_category%' URL placeholder */
    function my_links_filter_post_type_link( $link, $post ) {
      if ( $post->post_type == 'my_links' ) {
        if ( $cats = get_the_terms( $post->ID, 'my_links_category' ) ) {
          $link = str_replace( '%my_links_category%', current( $cats )->slug, $link );
        } else {
            $link = str_replace( '%my_links_category%/', '', $link );
        }
      }
      return $link;
    }
    
    /* Default taxonomy if none is selected on update or publish */
    public function my_links_category_set_default_object_terms( $post_id, $post ) {
        if ( 'publish' === $post->post_status ) {
            $defaults = array(
                'my_links_category' => array( 'Uncategorized' ),
            );
            $taxonomies = get_object_taxonomies( $post->post_type );
            foreach ( (array) $taxonomies as $taxonomy ) {
                $terms = wp_get_post_terms( $post_id, $taxonomy );
                if ( empty( $terms ) && array_key_exists( $taxonomy, $defaults ) ) {
                    wp_set_object_terms( $post_id, $defaults[$taxonomy], $taxonomy );
                }
            }
        }
    }

	/* Update the messaging in the admin */
	public function update_messages( $messages ) {
		global $post, $post_ID;
		$messages['my_links'] = array(
			0 => '',
			1 => __('Link updated.'),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Link updated.'),
			5 => isset($_GET['revision']) ? sprintf( __('Link restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __('Link published.'),
			7 => __('Link saved.'),
			8 => __('Link submitted.'),
			9 => __('Link scheduled for: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ),
			10 => __('Link draft updated.'),
		);
		return $messages;
	}

	/* Add contextual help tab in the admin */
	public function update_contextual_help( $contextual_help, $screen_id, $screen ) {
        
		// Link Edit Screen
		if ( 'edit-my_links' == $screen->id ) {
			$contextual_help = '<h3>Links</h3>
			<p>Links are similar to a site\'s blogroll. You can see a list of them on this page in reverse chronological order.</p>
			<p>You can view/edit the details of each link by clicking on its name, or you can perform bulk actions using the dropdown menu and selecting multiple items.</p>';

		// Single Link Edit Screen
		} elseif ( 'my_links' == $screen->id ) {
			$contextual_help = '<h3>Editing Link</h3>
			<p>This page allows you to view/modify link details. Please make sure to fill out the name, website address fields, and define a date for which the original article being linked to was published; the rest are optional. If a category is not defined it will default to "Uncategorized".</p>';

		}
        
		return $contextual_help;
	}
    
    /* Remove Meta Boxes */
    public function my_link_remove_metaboxes () {
        remove_meta_box( 'slugdiv', 'my_links', 'normal' );
        remove_meta_box( 'dynwid', 'my_links', 'side' );
        remove_meta_box( 'wpseo_meta', 'my_links', 'normal' );
    }
    
    /* Register and enqueue admin stylesheet */
	public function meta_box_admin_style() {
	    wp_enqueue_style(self::slug.'-admin', plugins_url('css/admin.css', __FILE__));
	}
    
    public function meta_box_admin_script() {
        wp_enqueue_script(self::slug.'-admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '', TRUE);
        wp_enqueue_script('jquery-ui-datepicker', array('jquery'), '', TRUE);
    }
    
    /* Add a custom meta box for inputs in the new content type*/
	public function add_custom_meta_box( $post_type ) {
        add_meta_box(
	        'my_links_date',
	        __( 'Article Date', self::slug ),
	        array( $this, 'my_links_date_meta_box_content' ),
	        self::slug,
	        'side',
	        'default'
	    );
	    add_meta_box(
	        'my_links_url',
	        __( 'Web Address', self::slug ),
	        array( $this, 'my_links_url_meta_box_content' ),
	        self::slug,
	        'normal',
	        'default'
	    );
        add_meta_box(
	        'my_links_description',
	        __( 'Description', self::slug ),
	        array( $this, 'my_links_description_meta_box_content' ),
	        self::slug,
	        'normal',
	        'default'
	    );
        add_meta_box(
	        'my_links_target',
	        __( 'Link Target', self::slug ),
	        array( $this, 'my_links_target_meta_box_content' ),
	        self::slug,
	        'normal',
	        'default'
	    );
	}
    
    public function my_links_date_meta_box_content( $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( plugin_basename( __FILE__ ), 'my_links_date_meta_box_content_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_link-date', true );

		$html = '<p>';
		$html .= '<input class="datepicker" type="text" name="link-date" id="link-date" style="width:100%" value="'.( !empty($value) ? $value : "" ).'" />';
		$html .= '<p>Specify the publish date of the article.</p>';
		$html .= '</p>';
	    echo $html;
	}
    
	public function my_links_url_meta_box_content( $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( plugin_basename( __FILE__ ), 'my_links_url_meta_box_content_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_link-url', true );

		$html = '<p>';
		$html .= '<input type="url" name="link-url" id="link-url" style="width:100%" placeholder="http://" value="'.( !empty($value) ? $value : "" ).'" />';
		$html .= '<p>Example: <code>http://wordpress.org/</code> — don’t forget the <code>http://</code></p>';
		$html .= '</p>';
	    echo $html;
	}
    
    public function my_links_description_meta_box_content( $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( plugin_basename( __FILE__ ), 'my_links_description_meta_box_content_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_link-description', true );

		$html = '<p>';
		$html .= '<input type="text" name="link-description" id="link-description" style="width:100%" placeholder="" value="'.( !empty($value) ? $value : "" ).'" />';
		$html .= '<p>This is optional.</p>';
		$html .= '</p>';
	    echo $html;
	}
    
    public function my_links_target_meta_box_content( $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( plugin_basename( __FILE__ ), 'my_links_target_meta_box_content_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_link-target', true );
        
        if (empty($value)) {
            $value = '_blank';
        }

		$html = '<p>';
        $html .= '<p><label for="link_target_blank" class="selectit"><input id="link_target_blank" type="radio" name="link-target" value="_blank" '.( !empty($value == '_blank') ? 'checked="checked"' : '' ).' /><code>_blank</code> &mdash; new window or tab</label></p><p><label for="link_target_top" class="selectit"><input id="link_target_top" type="radio" name="link-target" value="_top" '.( !empty($value == '_top') ? 'checked="checked"' : '' ).' />
<code>_top</code> &mdash; current window or tab, with no frames</label></p><p><label for="link_target_none" class="selectit"><input id="link_target_none" type="radio" name="link-target" value="_none" '.( !empty($value == '_none') ? 'checked="checked"' : '' ).' /><code>_none</code> &mdash; same window or tab</label></p>';
		$html .= '<p>Choose the target frame for your link.</p>';
		$html .= '</p>';
	    echo $html;

	}

	/* Save the custom meta box fields to the postmeta table */
	public function save_custom_meta_box( $post_id ) {
		// Checks save status
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
        $is_date_valid_nonce = ( isset( $_POST[ 'my_links_date_meta_box_content_nonce' ] ) && wp_verify_nonce( $_POST[ 'my_links_date_meta_box_content_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
		$is_url_valid_nonce = ( isset( $_POST[ 'my_links_url_meta_box_content_nonce' ] ) && wp_verify_nonce( $_POST[ 'my_links_url_meta_box_content_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
        $is_description_valid_nonce = ( isset( $_POST[ 'my_links_description_meta_box_content_nonce' ] ) && wp_verify_nonce( $_POST[ 'my_links_description_meta_box_content_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
        $is_target_valid_nonce = ( isset( $_POST[ 'my_links_target_meta_box_content_nonce' ] ) && wp_verify_nonce( $_POST[ 'my_links_target_meta_box_content_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

		// Exits script depending on save status
		if ( $is_autosave || $is_revision || !$is_url_valid_nonce || !$is_description_valid_nonce || !$is_target_valid_nonce ) {
			return;
		}

		// Checks for input and sanitizes/saves if needed
        if ( isset($_POST['link-date']) && !empty($_POST['link-date']) ) {
			update_post_meta( $post_id, '_link-date', sanitize_text_field( $_POST[ 'link-date' ] ) );
		}
        
		if ( isset($_POST['link-url']) && !empty($_POST['link-url']) ) {
			update_post_meta( $post_id, '_link-url', sanitize_text_field( $_POST[ 'link-url' ] ) );
		}
                           
        if ( isset($_POST['link-description']) && !empty($_POST['link-description']) ) {
			update_post_meta( $post_id, '_link-description', sanitize_text_field( $_POST[ 'link-description' ] ) );
		}
        
        if ( isset($_POST['link-target']) && !empty($_POST['link-target']) ) {
			update_post_meta( $post_id, '_link-target', sanitize_text_field( $_POST[ 'link-target' ] ) );
		}
	}
    
    // Exclude Post Type from Search
    function my_links_search_filter($query) {
        if ( !$query->is_admin && $query->is_main_query() ) {
            if ( $query->is_search ) {
                $query->set('post_type', array('page', 'post') );
            }
        }
        return $query;
    }
    
    /* My Links Shortcode */
    function press_links_shortcode( $atts ) { 
        // Variables
        $output = '';
        
        // Get Shortcode Attributes
        extract( shortcode_atts( array(  
            'limit' => '10',  
            'orderby' => 'meta_value_num',
            'meta_key' => '_link-date',
            'category' => ''
        ), $atts ) ); 
        
        // Build the Loop Based on the Defined Attributes
        if ($category !== '' && $category !== 'all') {
            $loop = new WP_Query( array( 
                'post_type' => 'my_links', 
                'posts_per_page' => $limit,
                'orderby' => $orderby, 
                'tax_query' => array( 
                    array(
                        'taxonomy' => 'my_links_category', 
                        'field' => 'slug', 
                        'terms' => $category 
                    )
                ) 
            ) ); 
        } else {
            $output .= '<h4>All Categories</h4>';
            $loop = new WP_Query( array ( 'post_type' => 'my_links', 'posts_per_page' => $limit, 'orderby' => $orderby ) );
        }
        
        // Looping through the posts and building the HTML structure.  
        if ($loop) {  
            
            $output .= '<ul class="press-link clearfix">';
            
            while ($loop->have_posts()) {  
                $loop->the_post(); 
                
                // Post Meta
                $date = get_post_meta($loop->post->ID, '_link-date', true);
                $url = get_post_meta($loop->post->ID, "_link-url", true);
                $description = get_post_meta($loop->post->ID, "_link-description", true);
                $target = get_post_meta($loop->post->ID, "_link-target", true);
                
                $output .= '<li>';
                if ($date) {
                     $output .= ''.sprintf(__('<time class="updated" datetime="%1$s" pubdate>%2$s</time>', 'zonediet'), $date, $date).' ';
                }
                $output .= '<a href="'.$url.'" title="'.get_the_title().'" target="'.$target.'">'.get_the_title().'</a>';    
                if ($description) {
                    $output .= '<div class="link-description">'.$description.'</div>';
                }
                $output .= '</li>';
            }
            
            // Read More Link
            if ($category !== '' && $category !== 'all') {
                $link = get_term_link($category, 'my_links_category');
                $term = get_term_by('slug', $category, 'my_links_category');
                $output .= '<li><a class="read-more" href="'.$link.'" title="'.$term->name.' Archive">Read More</a></li>';
            }
            
            $output .= '</ul>';
            
            wp_reset_postdata();
            
        } else {  
            $output = 'Sorry, no links&hellip;';
        }
        
        // Now we are returning the HTML code back to the place from where the shortcode was called.          
        return $output;
    } 
    
    /* Output */
	function include_template_function( $template_path ) {
	    if ( get_post_type() == 'my_links' ) {
	        if ( is_tax('my_links_category') ) {
	            // checks if the file exists in the theme first, otherwise serve the file from the plugin
	            if ( $theme_file = locate_template( array ( 'taxonomy-my_links_category.php' ) ) ) {
	                $template_path = $theme_file;
	            } else {
	                $template_path = plugin_dir_path( __FILE__ ) . 'templates/taxonomy-my_links_category.php';
	            }
	        }
	    }
	    return $template_path;
	}

}
add_action('plugins_loaded', array( 'My_Links', 'get_instance' ) );
