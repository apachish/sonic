<?php

namespace NIKSEO\callbacks;
class Menu
{
    public $ghost_callback;
    public function __construct(){
        $this->ghost_callback = new NikCallBacks();

        add_action('admin_enqueue_scripts', array($this->ghost_callback, 'enqueue_wp_custom_scripts'));
        add_action('admin_menu', array($this, 'my_menu_pages'));
    }
    public function my_menu_pages(){

        add_menu_page(
            __('Sonic', 'sonic'),            // Page title (shown on the page)
            __('Sonic', 'sonic'),            // Menu title (shown in sidebar)
            'manage_options',
            'nik_seo',                      // Parent slug
            array($this->ghost_callback, 'index') , // Callback
            NIKSEO_PLUGIN_URL . '/assets/img/logo.png'
        );

        add_submenu_page(
            'nik_seo',                      // Parent slug
            __('Settings', 'sonic'),  // Page title (shown at top of page)
            __('Settings', 'sonic'),       // Menu title (shown in sidebar)
            'manage_options',
            'nik_seo',                     // Submenu slug (same as parent menu slug)
            array($this->ghost_callback, 'index')  // Same callback
        );


        add_submenu_page(
            'nik_seo',
            __('Create Post', 'sonic'),
            __('Create Post', 'sonic'),
            'manage_options',
            'nik_seo_content_creator',
            array($this->ghost_callback,'content_creator')
        );

        add_submenu_page(
            'nik_seo',
            __('Assistant', 'sonic'),
            __('Assistant', 'sonic'),
            'manage_options',
            'nik_keyword_research',
            array($this->ghost_callback,'keyword_research')
        );

        add_submenu_page(
            'nik_seo',
            __('Posts', 'sonic'),
            __('Posts', 'sonic'),
            'manage_options',
            'nik_seo_posts',
            array($this->ghost_callback,'Posts')
        );

    }
}