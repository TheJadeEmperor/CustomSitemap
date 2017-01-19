<?php
/*
  Plugin Name: Custom Sitemap | Auto Sitemap
  Description: Configuration options found under <a href="options-general.php?page=auto-sitemap-admin">Custom Sitemap Settings</a>
  Author: BL
  Version: 0.2
 */ 


// Hook in the options page
add_action('admin_menu', 'auto_sitemap_menu');
add_action('plugins_loaded', 'auto_plugins_loaded');

function auto_plugins_loaded() {
    if (is_super_admin()) {
        add_action('add_meta_boxes', 'auto_add_meta_boxes');
        add_action('save_post', 'auto_save_post');
    }
}

//add metabox to pages/posts screen 
function auto_add_meta_boxes($post_type) {
    add_meta_box(
            'auto_sectionid', __('Custom Sitemap', 'sitemap'), 'auto_custom_box', $post_type, 'side', 'high'
    );

    //add_meta_box('id', 'metabox name', 'name', $post_type, 'location', 'priority');
}


function auto_custom_box() {

    wp_nonce_field(plugin_basename(__FILE__), 'sitemap_noncename');

    $checked = "";
    if (isset($_GET['post']) && get_post_meta($_GET['post'], 'auto_checkbox') != false) {
        $checked = ' checked="checked" ';

    }
    
    
    $auto_option = get_post_meta(get_the_ID(), 'auto_sitemap_category', true);
    
     
    $string = get_option('sitemap_categories');
    
    /* Use tab and newline as tokenizing characters  */
    $tok = strtok($string, "\n\t");
   // $tok = rtrim($tok);

    while ($tok !== false) {
        
        $cat = rtrim($tok);
        
        if($auto_option == $cat) $sel = 'selected'; else $sel = '';
        
        $auto_category .= '<option '.$sel.'>'.$cat.'</option>';
        $tok = strtok("\n\t");
        //$tok = rtrim($tok);

    }

    echo '<p>';
    echo '<input type="checkbox" id="auto_checkbox" name="auto_checkbox" ' . $checked . '/>';
    echo '<label for="auto_checkbox">';
    _e(" Add to Sitemap", 'add_to_sitemap');
    echo '</label> ';
    echo '</p>';


    echo '<p>Category <select id="auto_sitemap_category" name="auto_sitemap_category">'
    . '<option></option>'.$auto_category.'</select></p>';

    
    echo '<p><a href="options-general.php?page=auto-sitemap-admin">Custom Sitemap Settings</a></p>';
}

//save the postmeta details
function auto_save_post($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { //skip if auto-saving
        return false;
    }

    //verify the nonce
    if (!isset($_POST['sitemap_noncename']) || !wp_verify_nonce($_POST['sitemap_noncename'], plugin_basename(__FILE__))) {
        return false;
    }


    if (isset($_POST['auto_checkbox'])) {
        add_post_meta($post_id, 'auto_checkbox', 1, true);
    } else {
        delete_post_meta($post_id, 'auto_checkbox');
    }
    
    
    if ( !empty($_POST['auto_sitemap_category'])) {
        update_post_meta($post_id, 'auto_sitemap_category', $_POST['auto_sitemap_category']);
    } 
	else {
        delete_post_meta($post_id, 'auto_sitemap_category');
    }
    

    $post_type = get_post_type($post_id);
}

/**
 * Add the options page
 */
function auto_sitemap_menu() {
    if (function_exists('add_options_page')) {
        add_options_page(
                'Auto Sitemap Page', 'Auto Sitemap Options', 'manage_options', 'auto-sitemap-admin', 'auto_sitemap_admin'
        );
    }
}

function auto_sitemap_admin() {

    echo '<h1>How to show the sitemap</h1>';
    
    echo 'Use the shortcode [show_sitemap]';
    
    
    // WP_Query arguments
    $args = array(
        'post_type' => 'any',
        'orderby' => 'title',
        'meta_query' => array(
            array(
                'key' => 'auto_checkbox',
            ),
        ),
    );

    // The Query
    $query = new WP_Query($args);

    // The Loop
    if ($query->have_posts()) {
        echo '<h2>Current Posts displayed on sitemap</h2>';
        while ($query->have_posts()) {
            $query->the_post();
            
            $the_id = get_the_id();
            
            echo $the_id . ' - '.substr(get_the_title(), 0, 40).'... - '
                    . '<a href="post.php?post='.$the_id.'&action=edit" target="_BLANK">edit post</a> - ';
            echo '<a href="'.get_permalink($the_id).'" target="_BLANK">view post</a> <br />';

        }
    } 
    else { // no posts found
       echo '<p>No posts found</p>'; 
    }
    ?>


    <h2>Sitemap Categories</h2>
    <form method="post" action="options.php">
    <?php settings_fields('test-plugin-settings-group'); ?>
    <?php do_settings_sections('test-plugin-settings-group'); ?>

	<textarea name="sitemap_categories" rows="10" cols="60"><?php 
            echo get_option('sitemap_categories'); ?></textarea>

	<p>Type in the categories, one line per category. Please do not paste from 
        MS Word</p>
            <?php submit_button(); ?>
    </form>
    <?php     
}

//register settings
add_action('admin_init', 'register_test_plugin_settings');

function register_test_plugin_settings() {
    //register our settings
    register_setting('test-plugin-settings-group', 'sitemap_categories'); 
}


// Add Shortcode
function generate_sitemap( $atts ) {
    
//	$string = get_option('sitemap_categories');
    
    $all_categories = array();
    
    
    // WP_Query arguments
    $args = array(
        'post_type' => 'any',
        'orderby' => 'title',
        'meta_query' => array(
            array(
                'key' => 'auto_checkbox',
                'key' => 'auto_sitemap_category'
            ),
        ),
    );

    // The Query
    $query = new WP_Query($args);

    // The Loop
    if ($query->have_posts()) {
        $output .= '<h2>Current Posts displayed on sitemap</h2>';
        
        while ($query->have_posts()) {
			//get post details
			$query->the_post();
            
            $the_id = get_the_id();
            
            $post_meta = get_post_meta($the_id);

			//the category of the current sitemap entry
            $current_category = $post_meta['auto_sitemap_category'][0];
            
			//the sitemap entry to be displayed
            $sitemap_entry = '<a href="'.get_permalink($the_id).'" target="_BLANK" title="'.$the_id.'">'.get_the_title().'</a> <br />';
			
			//store this entry in the $all_categories array
			$all_categories[$current_category][$the_id] = $sitemap_entry;
			
        }
        
		//echo '<pre>'; print_r($all_categories);
		
		
        foreach($all_categories as $this_category => $all_posts) {
			
            
			$output .= '<h3>'.$this_category.'</h3>';
			
			if(is_array($all_posts))
			foreach($all_posts as $the_id => $sitemap_entry) {
				
				$output .= $sitemap_entry;
			}
			
            
            $output .= '<br>';
        }
        
    } 
    else { // no posts found
       $output = '<h3>No posts found</h3>'; 
    }
        
    
    return $output; 
}

add_shortcode( 'show_sitemap', 'generate_sitemap' );