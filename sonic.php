<?php
/**
 * Plugin Name: Sonic (Your AI Agent)
 * Text Domain: sonic
 * Description: A sophisticated AI-powered plugin that generates new posts and seamlessly updates existing content on your website. With just one Click!
 * Author: Niklink
 * Plugin URI: https://niklinkagency.com
 * Version: 1.00
 * Requires PHP: 7.0
 */

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
load_plugin_textdomain( 'sonic', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

$base = new \NIKSEO\src\Base();

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sonic_settings_link');
function sonic_settings_link($links) {
    $settings_link = '<a href="'.admin_url('admin.php?page=nik_seo').'">'.__('Settings','default').'</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// redirect to settings page after activation
register_activation_hook(__FILE__, [$base, 'activate']);

add_action('admin_init', 'sonic_redirect_after_activation');
function sonic_redirect_after_activation() {
    if (get_option('sonic_do_activation_redirect', false)) {
        delete_option('sonic_do_activation_redirect');
        wp_redirect(admin_url('admin.php?page=nik_seo'));
        exit;
    }
}
