<?php
/*
* Plugin Name: Social Monitor
* Version: 1.0
* Plugin URI: http://www.hughlashbrooke.com/
* Description: This is your starter template for your next WordPress plugin.
* Author: Hugh Lashbrooke
* Author URI: http://www.hughlashbrooke.com/
* Requires at least: 4.0
* Tested up to: 4.0
*
* Text Domain: social-monitor
* Domain Path: /lang/
*
* @package WordPress
* @author Hugh Lashbrooke
* @since 1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;
require_once(ABSPATH . 'wp-admin/includes/screen.php');

// Load plugin class files
require_once( 'includes/class-social-monitor.php' );
require_once( 'includes/class-social-monitor-settings.php' );
// Load plugin libraries
require_once( 'includes/lib/class-social-monitor-admin-api.php' );
require_once( 'includes/lib/class-social-monitor-post-type.php' );
require_once( 'includes/lib/class-social-monitor-taxonomy.php' );

/**
* Returns the main instance of Social_Monitor to prevent the need to use globals.
*
* @since  1.0.0
* @return object Social_Monitor
*/
function Social_Monitor () {
  $instance = Social_Monitor::instance( __FILE__, '1.0.0' );

  $instance->register_taxonomy('sm_social_tag', 'Social Tags', 'Social Tag', 'sm_social_post' );

  $args =  array(
    'labels' => array(
      'name' => 'Social Posts',
      'singular_name' => "Social Post"
    ),
    'taxonomies' => array( 'category', 'sm_social_tag' ),
    'supports' => array('title')
  );

  $instance->register_post_type('sm_social_post', "Social Posts", "Social Post", 'Imported posts from social networks', $args );

  if ( is_null( $instance->settings ) ) {
    $instance->settings = Social_Monitor_Settings::instance( $instance );
  }

  return $instance;
}

add_action( 'pre_get_posts', 'wpa_order_social_posts' );

function wpa_order_social_posts( $query ){
  if( !is_admin() )
  return;

  $orderby = $query->get( 'orderby');
  $screen = get_current_screen();

  if ($screen) {
    if( 'edit' == $screen->base
    && 'sm_social_post' == $screen->post_type
    && !isset( $_GET['orderby'] ) ){
      $query->set( 'orderby', 'meta_value' );
      $query->set( 'meta_key', 'created' );
      $query->set( 'order', 'DESC' );
    }
  }
}

add_filter( 'manage_sm_social_post_posts_columns', 'add_social_post_header_columns' ) ;

function add_social_post_header_columns( $columns ) {

  $columns = array(
    'cb' => '<input type="checkbox" />',
    'title' => __( 'Social Post' ),
    'thumbnail' => __( 'Thumbnail' ),
    'original_author' => __( 'Original Author' ),
    'original_url' => __( 'Original URL' ),
    'created' => __( 'Original Post Date' ),
    'date' => __( 'Date' )
  );

  return $columns;

}

add_action( 'manage_sm_social_post_posts_custom_column', 'add_social_post_columns', 10, 2 );

function add_social_post_columns( $column, $post_id ) {

  global $post;

  switch( $column ) {

    case 'thumbnail':
    echo "<img src='" . get_post_meta( $post_id, 'photo_url', true) . "' width=120 />";
    break;

    case 'created':
    echo date( 'F j, Y - h:ia', get_post_meta($post_id, 'created', true));
    break;

    case 'original_author':
    echo get_post_meta( $post_id, 'original_author', true );
    break;

    case 'original_url':
    $url = get_post_meta( $post_id, 'original_url', true );
    echo "<a href='" . $url . "'>" . $url . "</a>";
    break;

    case 'created':
    $created = get_post_meta( $post_id, 'created', true );
    echo ($created ? date("F j, Y - H:ia", $created) : "Unknown");
    break;
  }

}

add_filter( 'manage_edit-sm_social_post_sortable_columns', 'sm_social_post_sortable' );

function sm_social_post_sortable($columns) {
  $columns['created'] = 'created';
  return $columns;
}

add_action( 'pre_get_posts', 'sm_social_post_orderby' );
function sm_social_post_orderby( $query ) {
  if( ! is_admin() )
  return;

  $orderby = $query->get( 'orderby');

  if( 'created' == $orderby ) {
    $query->set('meta_key','created');
    $query->set('orderby','meta_value_num');
  }
}

add_filter( 'cron_schedules', 'add_custom_cron_intervals', 10, 1 );

function add_custom_cron_intervals( $schedules ) {

  $schedules['ten_minutes'] = array(
    'interval'	=> 600,
    'display'	=> 'Once Every 10 Minutes'
  );

  return (array)$schedules;

}

register_deactivation_hook( __FILE__, 'social_media_plugin_deactivate' );

function social_media_plugin_deactivate() {

  wp_clear_scheduled_hook( 'update_social_posts' );

}


register_activation_hook( __FILE__, 'social_media_plugin_register' );

function social_media_plugin_register() {

  wp_schedule_event( time(), 'ten_minutes', 'update_social_posts' );

}

add_action( 'update_social_posts', 'sm_update_social_monitor' );

add_action('add_meta_boxes', 'add_instagram_meta_box');

function add_instagram_meta_box() {
  add_meta_box("sm_instagram_details", "Instagram Details", "sm_instagram_details_markup", "sm_social_post", "normal");
}

function sm_instagram_details_markup($post) {
  ?>
  <div>
    <div class="input-group">
      <h4>Created</h4>
      <?php $created = get_post_meta($post->ID, 'created', true); ?>
      <?php $dateStr = $created ? date("F j, Y - H:ia", $created) : "Unknown"; ?>
      <p><?php echo $dateStr ?></p>
    </div>
    <div class="input-group">
      <h4>Text</h4>
      <p><?php echo get_post_meta($post->ID, 'text', true) ?></p>
    </div>
    <div class="input-group">
      <h4>Author</h4>
      <p><?php echo get_post_meta($post->ID, 'original_author', true) ?></p>
    </div>
    <div class="input-group">
      <h4>Photo URL</h4>
      <?php $url = get_post_meta($post->ID, 'photo_url', true);
      if ($url) {
        echo "<p>" . $url . "</p>" . "<img src='" . $url . "' />";
      } else {
        echo "<p>N/A</p>";
      }
      ?>
    </div>
    <div class="input-group">
      <h4>Original URL</h4>
      <p><?php echo get_post_meta($post->ID, 'original_url', true) ?></p>
    </div>
  </div>
  <?php
}
add_action('admin_menu', 'create_social_posts_submenu_links');

function create_social_posts_submenu_links() {
  add_submenu_page('edit.php?post_type=sm_social_post', 'Import Social Posts', 'Import', 'edit_posts', 'sm_import_posts_menu', 'sm_import_menu_page');
}

function sm_import_menu_page() {
  echo "<div class='wrap'>";
  echo "<div class='buttons-wrapper'>";
  echo "<a href='/wp/wp-admin/edit.php?post_type=sm_social_post&page=sm_import_posts_menu&action=update_social_monitor&age=newer'>Import Newer Posts</a>";
  echo "<a href='/wp/wp-admin/edit.php?post_type=sm_social_post&page=sm_import_posts_menu&action=update_social_monitor&age=older'>Import Older Posts</a>";
  echo "</div>";
  echo "</div>";
  if (array_key_exists('age', $_GET)) {
    $new_posts = ($_GET['age'] == 'newer');
  } else {
    $new_posts = true;
  }

  render_recent_posts($new_posts);
}

if( isset( $_GET['action'] ) && $_GET['action'] == 'update_social_monitor' ){
  add_action( 'init', 'sm_update_social_monitor' );
}

function sm_update_social_monitor(){
  sm_update_instagram_monitor();
}

function sm_update_instagram_monitor($new_posts = true) {
  // For importing NEWER posts, pull with min_tag_id = to the min_tag_id of the most recent post.
  // For OLDER posts, pull with the endpoint to the oldest posts next_url

  if (array_key_exists('age', $_GET)) {
    $new_posts = ($_GET['age'] == 'newer');
  }

  $social_monitor = new Social_Monitor();
  $posts_and_tag_id = $social_monitor->get_instagram_posts($new_posts);

  $posts = $posts_and_tag_id['posts'];
  $next_url = $posts_and_tag_id['next_url'];

  global $new_post_count;
  $new_post_count = 0;

  foreach ($posts as $social_post) {
    $videoUrl = NULL;

    $instagram_post = new Instagram_Post($social_post->id, $social_post->caption->text, $social_post->user->username, $social_post->images->standard_resolution->url, $videoUrl, $social_post->link, $social_post->created_time, $next_url);
    $results = get_posts( array(
      'post_type' => 'sm_social_post',
      'posts_per_page' => 1,
      'meta_key' => 'service_id',
      'meta_value' => $social_post->id,
      'post_status' => array('draft', 'publish', 'future', 'pending', 'trash')
    ) );

    if( count( $results ) > 0 ){
      break;
    }

    $new_post_id = $instagram_post->save();
    $new_post_count++;
    $GLOBALS['new_post_count'] = $new_post_count;
  }
}


function render_recent_posts($new_posts = true) {

  if (array_key_exists('new_post_count', $GLOBALS) && $GLOBALS['new_post_count'] > 0) {
    $new_post_count = $GLOBALS['new_post_count'];
    echo $new_post_count . " new posts imported.";
    $new_posts = get_posts( array(
      'post_type' => 'sm_social_post',
      'posts_per_page' => $new_post_count,
      'orderby' => 'post_date',
      'order' => ($new_posts ? 'DESC' : 'ASC'),
      'post_status' => array('draft', 'publish', 'future', 'pending')
    ));

    foreach($new_posts as $new_post):
      $author = get_post_meta($new_post->ID, 'original_author', true);
      $text = get_post_meta($new_post->ID, 'text', true);
      $photo_url = get_post_meta($new_post->ID, 'photo_url', true);

      echo "<figure class='new-post'>";
      echo "<img src='" . $photo_url . "' width='200' class='new-post-img'";
      echo "<figcaption><p>'" . $text . "' - " . $author . "</p></figcaption>";
      echo "</figure>";
    endforeach;
  } else {
    echo "No new posts imported.";
  }
}

Social_Monitor();
