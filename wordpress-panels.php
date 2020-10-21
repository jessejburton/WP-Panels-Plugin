<?php

/**
 * @package WordPress Panels
 *
**/
/*
Plugin Name: Burton Media Panels
Plugin URI: https://www.burtonmediainc.com/plugins/burtonmedia-panels
Description:
Version: 1.0.0
Author: Jesse James Burton & Samuel Keullen Passos
Author URI: https://www.burtonmediainc.com
License: GPLv2 or Later
Text Domain: burtonmedia-panels
GIT: https://github.com/jessejburton/WP-Panels-Plugin
*/

/* Include Styles */
function add_panel_plugin_styles() {
  wp_enqueue_style( 'wordpress-panels-styles', plugins_url('wordpress-panels.css',__FILE__ ), array(), '1.1', 'all');
}
add_action( 'wp_enqueue_scripts', 'add_panel_plugin_styles' );

/* Include Scripts */
function add_panel_plugin_script() {
  wp_enqueue_script( 'wordpress-panels-scripts', plugins_url('wordpress-panels.js',__FILE__ ), array(), '1.1', 'all', false);
}
add_action( 'wp_enqueue_scripts', 'add_panel_plugin_script' );

function get_reviews( $data ) {

	// setup query argument
	$args = array(
    "posts_per_page"   => 20,
    "paged"            => $data['page'],
    'post_type'        => 'reviews',
    'meta_key'			   => 'review_date',
    'orderby'			     => 'meta_value'
  );

	// get posts
	$posts = get_posts($args);

	// add custom field data to posts array
	foreach ($posts as $key => $post) {
      $posts[$key]->acf = get_fields($post->ID);
      $posts[$key]->review_date = date('F Y', strtotime(get_field('review_date', $post->ID, false, false)));
			$posts[$key]->link = get_field('article_link', $post->ID, false, false);
			$posts[$key]->image = get_the_post_thumbnail_url($post->ID);
      $posts[$key]->shows = get_the_terms($post, 'shows');
	}
	return $posts;
}

/*
 * Register Panels shortcode
 */
function register_panels_shortcodes() {
  add_shortcode( 'wp_panels', 'shortcode_wordpress_panels' );
}
add_action( 'init', 'register_panels_shortcodes' );

/*
* Panels Shortcode Callback
*/
function shortcode_wp_panels( $atts ) {
  global $wp_query,
         $post;

  $atts = shortcode_atts( array(
      'set' => '',
      'max_posts' => 30,
  ), $atts );

  if($atts['set'] === ''){
    // get all terms in the taxonomy
    $terms = get_terms( 'set' );
    // convert array of term objects to array of term IDs
    $term_slugs = wp_list_pluck( $terms, 'slug' );
  } else {
    $term_slugs = array( sanitize_title( $atts['set'] ) );
  }

  $loop = new WP_Query( array(
      'posts_per_page'    => sanitize_title( $atts['max_posts'] ),
      'post_type'         => 'panel',
      'tax_query'         => array( array(
          'taxonomy'  => 'set',
          'field'     => 'slug',
          'terms'     => $term_slugs
      ) )
  ) );

  if( ! $loop->have_posts() ) {
    ob_start();
      echo 'No reviews found';
    return ob_get_clean();
  }

  ob_start();
    while( $loop->have_posts() ) {
        $loop->the_post();

        require('templates/panel.php');       // Use the passed in template

    }
  return ob_get_clean();

  wp_reset_postdata();
}
