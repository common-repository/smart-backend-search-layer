<?php
/*
Plugin Name:       Smart Backend Search Layer
Plugin URI:        https://guaven.com/sbsl
Description:       Smart Backend Search Layer for WordPress Themes
Version:           1.1.1
Author:            Guaven Labs
Author URI:        https://guaven.com
*/

if (!defined('ABSPATH')) {
    die;
}

$code_version='1.0.0.0';

define('Guaven_SBSL_DIR', plugin_dir_path(__FILE__));

if (empty($_GET["guaven_sbsl_skip"])) {

  require_once Guaven_SBSL_DIR . 'core/core.php';
  require_once Guaven_SBSL_DIR . 'admin/class-admin.php';
  require_once Guaven_SBSL_DIR . 'public/class-front.php';

  $guaven_sbsl_front = new Guaven_SBSL_Front($code_version);
  $guaven_sbsl_admin = new Guaven_SBSL_Admin($code_version);

  add_filter('posts_orderby', array(
      $guaven_sbsl_front,
      'backend_search_orderby'
    ));

  add_filter('posts_search', array(
      $guaven_sbsl_front,
      'backend_search_replacer'
    ), 10001);

  add_action('posts_where', array(
          $guaven_sbsl_front,
          'backend_search_filter'
      ), 10001);

  add_action('admin_menu', array(
      $guaven_sbsl_admin,
      'admin_menu'
    ));

  add_action('wp_ajax_wp_sbsl_rebuild', array(
          $guaven_sbsl_admin,
          'wp_sbsl_rebuild_callback'
        ));

  add_action('admin_footer', array(
          $guaven_sbsl_admin,
          'do_rebuilder_at_footer'
      ));

  add_action('edit_post', array(
          $guaven_sbsl_admin,
          'cleaner_hook'
        ));

  add_action('edit_post', array(
      $guaven_sbsl_admin,
      'edit_hook_rebuilder'
  ));

}
