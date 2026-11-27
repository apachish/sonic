<?php
namespace NIKSEO\callbacks;
use NIKSEO\src\PostPublisher;

class NikCallBacks
{
    public string $notice;
    public function enqueue_wp_custom_scripts($hook)
    {
        if (strpos($hook, 'nik_seo') === false && strpos($hook, 'nik_keyword') === false) {
            return;
        }

        wp_enqueue_style('nikseo_admin_styles', NIKSEO_ASSETS . '/css/admin-css.css', [], filemtime(NIKSEO_ASSETS_DIR . '/css/admin-css.css'));
        wp_enqueue_script('nikseo_script', NIKSEO_ASSETS . '/js/admin-js.js', ['jquery'], filemtime(NIKSEO_ASSETS_DIR . '/js/admin-js.js'), true);
        wp_localize_script('nikseo_script', 'NIKSEO', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'content_create' => admin_url('admin.php') . '?page=nik_seo_content_creator',
            'sonic_posts' => admin_url('admin.php') . '?page=nik_seo_posts',
            'assets_url' => NIKSEO_ASSETS,
            'show_posts' => __('show posts', 'sonic'),
            'final_Submit' => __('Final Submit', 'sonic'),
            'next_step' => __('Next Step', 'sonic'),
            'link_title' => __('Link Title', 'sonic'),
            'destination_url' => __('Destination URL', 'sonic'),
            'recently_updated' => __('Recently updated', 'sonic'),
            'Updating' => __('Updating', 'sonic'),
            'cancel_process' => __('Cancel Process', 'sonic'),
            'Updated' => __('Updated', 'sonic'),
            'title' => __('Title', 'sonic'),
            'publish_date' => __('Publish Date', 'sonic'),
            'modify_date' => __('Modify Date', 'sonic'),
            'author' => __('Author', 'sonic'),
            'actions' => __('Actions', 'sonic'),
            'updating_posts' => __('Updating Posts', 'sonic'),
            'create_post' => __('Create Post', 'sonic'),
            'post_requested' => __('Post Requested', 'sonic'),
        ));
    }

    public function index()
    {
        $this->show('main.php', 'admin');
    }

    public function content_creator()
    {
        $cats = $this->get_hierarchical_terms('category');
        $args = [
            'categories' => $cats,
        ];
        $this->show('ai-content-creator.php', 'admin', $args);
    }

    public function keyword_research()
    {
        $cats = $this->get_hierarchical_terms('category');
        $args = [
            'categories' => $cats,
        ];
        $this->show('keyword_research.php', 'admin',$args);
    }

    public function setup()
    {
        $this->show('setup.php', 'admin');
    }

    public function posts()
    {
        $this->show('posts.php', 'admin');
    }
    public function show($file, $dir, $args = [])
    {
        require_once NIKSEO_PLUGIN_PATH . '/' . $dir . '/' . $file;
    }

    public function admin_notice($message)
    {
        $this->notice = $message;
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }

    public function show_admin_notices()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="notice notice-error">
            <p>
                <?php esc_html_e($this->notice); ?>
            </p>
        </div>
        <?php

    }

    public function get_hierarchical_terms($taxonomy, $args = [])
    {
        // Get all terms
        $terms = get_terms(array_merge([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ], $args));

        // find default category
        $default_cat_id = get_option('default_category');

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        // Index terms by their ID
        $term_map = [];
        foreach ($terms as $term) {
            $term_map[$term->term_id] = [
                'name' => $term->name,
                'id' => $term->term_id,
                'default' => $default_cat_id == $term->term_id,
                'parent' => $term->parent,
                'child' => [],
            ];
        }

        // Build the hierarchy
        $tree = [];
        foreach ($term_map as $id => &$term) {
            if ($term['parent'] && isset($term_map[$term['parent']])) {
                $term_map[$term['parent']]['child'][] = &$term;
            } else {
                $tree[] = &$term;
            }
        }

        return $tree;
    }

}