<?php

namespace NIKSEO\src;

class PostPublisher {

    /**
     * Publish a new post.
     *
     * @param string      $title
     * @param string      $content
     * @param string      $status
     * @param string      $type
     * @param array       $tags
     * @param array       $categories
     * @param string|null $featured_image_url
     * @return int|\WP_Error
     */
    public function publish($title, $content, $status = 'publish', $type = 'post', $tags = [], $categories = [], $featured_image_url = null, array $meta_data = [], $slug = null) {

        $post_data = [
            'post_title'    => wp_strip_all_tags($title),
            'post_content'  => $content ?? '',
            'post_status'   => $status,
            'post_type'     => $type,
            'tags_input'    => $tags,
            'post_category' => $categories
        ];

        if ($slug) {
            $post_data['post_name'] = sanitize_title($slug);
        }



        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            error_log('[PostPublisher] Failed to insert post: ' . $post_id->get_error_message());
            return $post_id;
        }

        if ($featured_image_url) {
            $this->set_featured_image_from_url($post_id, $featured_image_url);
        }

        if(!empty($meta_data)){
            foreach ( $meta_data as $key => $value ) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return $post_id;
    }

    /**
     * Download an external image and set it as the featured image for the post.
     *
     * @param int    $post_id
     * @param string $image_url
     */
    private function save_image($image_url, $post_id) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        error_log("[PostPublisher] Attempting to download image from URL: $image_url");

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            error_log('[PostPublisher] Image download failed: ' . $tmp->get_error_message());
            return false;
        }

        // Check file size
        if (!file_exists($tmp) || filesize($tmp) < 1000) {
            error_log('[PostPublisher] Downloaded file is too small or does not exist.');
            unlink($tmp);
            return false;
        }

        // Validate image type
        $image_info = getimagesize($tmp);
        if (!$image_info || strpos($image_info['mime'], 'image/') !== 0) {
            error_log('[PostPublisher] Downloaded file is not a valid image.');
            unlink($tmp);
            return false;
        }

        $file_array = [
            'name'     => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($attachment_id)) {
            error_log('[PostPublisher] media_handle_sideload failed: ' . $attachment_id->get_error_message());
            unlink($tmp);
            return false;
        }
        return $attachment_id;
    }

    private function set_featured_image_from_url($post_id, $image_url) {
        $save_image = $this->save_image($image_url, $post_id);
        if($save_image) {
            set_post_thumbnail($post_id, $save_image);
        }
    }

    private function replace_article_images($html, $images, $post_id)
    {
        return preg_replace_callback(
            '/\[sonic_image id="(\d+)"\]/',
            function ($matches) use ($images, $post_id) {
                $index = intval($matches[1]) - 1;

                if (!isset($images[$index])) {
                    return '';
                }
                $image_id = $this->save_image($images[$index], $post_id);
                if($image_id) {
                    $image_html = wp_get_attachment_image($image_id, 'large');
                    return '<div style="text-align: center">' . $image_html . '</div>';
                }
                return '';
            },
            $html
        );
    }

    public function get_post($post_id)
    {
        $post = get_post($post_id);

        if (!$post) {
            return null; // or handle error
        }

        return [
            'postContent' => apply_filters('the_content', $post->post_content),
            'title'   => get_the_title($post_id)
        ];
    }

    public function get_posts()
    {
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // get all posts
            'fields'         => 'ids', // performance optimization
        );

        $post_ids = get_posts($args);
        $titles = array();

        foreach ($post_ids as $post_id) {
            $titles[] = get_the_title($post_id);
        }

        return $titles;
    }

    public function update($post_id, $title = null, $content = null, $status = null, $tags = [], $categories = [], $featured_image_url = null, array $meta_data = [], $article_image = []) {

        if(!empty($article_image) && $content) {
            $content = $this->replace_article_images($content, $article_image, $post_id);
        }

        if (!get_post($post_id)) {
            return new \WP_Error('invalid_post', 'Post not found.');
        }

        $post_data = [
            'ID'           => $post_id,
        ];

        if ($title !== null) {
            $post_data['post_title'] = wp_strip_all_tags($title);
        }

        if ($content !== null) {
            $post_data['post_content'] = $content;
        }

        if ($status !== null) {
            $post_data['post_status'] = $status;
        }

        $result = wp_update_post($post_data, true);

        if (is_wp_error($result)) {
            error_log('[PostPublisher] Failed to update post: ' . $result->get_error_message());
            return $result;
        }

        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags, false);
        }

        if (!empty($categories)) {
            wp_set_post_categories($post_id, $categories, false);
        }

        if ($featured_image_url) {
            $this->set_featured_image_from_url($post_id, $featured_image_url);
        }

        if(!empty($meta_data)){
            foreach ( $meta_data as $key => $value ) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return $post_id;
    }

    public function update_status(int $post_id, string $status) {
        update_post_meta($post_id,'sonic_post_update', $status);
    }

    public function delete( $post_id ) {
        if ( current_user_can( 'delete_post', $post_id ) ) {
            $deleted = wp_delete_post( $post_id, true );

            if ( $deleted ) {
                return true; // Successfully deleted
            } else {
                return false; // Failed to delete
            }
        } else {
            return false; // User doesn't have permission
        }
    }


}
