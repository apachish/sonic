<?php

namespace NIKSEO\src;

use NIKSEO\callbacks\Menu;

class Base {
    public string $notice;
    public function __construct(){

        $this->Consts();
        $route = (defined('GHOST') && GHOST) ? 'http://dev.me' : 'https://niklinkagency.com';

        define( 'SONIC_END_POINT', $route);
        define( 'NIK_CONTENT_CREATE_ROUTE', SONIC_END_POINT . '/wp-json/nik/v1/seo');
        define( 'NIK_USER_ASSET_ROUTE', SONIC_END_POINT . '/wp-json/nik/v1/user');
        define( 'NIK_KEYWORD_RESEARCH_ROUTE', SONIC_END_POINT . '/wp-json/nik/v1/keyword');
        define( 'NIK_TOOLS', SONIC_END_POINT . '/wp-json/nik/v1/tools');

        // Dashboard Menus
        new Menu();

        // Activate Api Handler
        $api = new ApiHandler();
        $api->register();

        $receive_data = new Receive();
        $receive_data->register();
    }

    private function Consts(){
        define("NIKSEO_PLUGIN_PATH", dirname(plugin_dir_path(__FILE__)));
        define("NIKSEO_PLUGIN_URL", dirname(plugin_dir_url(__FILE__)));
        define("NIKSEO_ASSETS", dirname(plugin_dir_url(__FILE__)).'/assets');
        define("NIKSEO_ASSETS_DIR", dirname(plugin_dir_path(__FILE__)).'/assets');

        define("MODELS", [
            [
                'name' => __('GPT-5', 'sonic'),
                'value' => 'gpt'
            ],
            [
                'name' => __('Gemini', 'sonic'),
                'value' => 'gemini'
            ]
        ]);

        define("HINTS", [
            "sonic_settings" => __('Thank you for choosing Sonic! Here you should enter your API key. once it approves you have access to all of our tools. Hope you enjoy!', 'sonic'),
            "sonic_content_create" => __('Easily create a SEO-optimized post with this tool, using your preferred title and custom settings.', 'sonic'),
            "sonic_keywords" => __('This tool analyzes your website and posts, then suggest new post titles for your website. You can even publish the new post instantly!', 'sonic'),
            "sonic_posts" => __('Here you can see published posts and the posts being generated. it could take some time to create a post but we assure you the outcome is spectacular', 'sonic')
        ]);
    }

    public function activate() {
        foreach (HINTS as $key => $value){
            add_option($key,1);
        }
        update_option('sonic_do_activation_redirect', true);
    }
}