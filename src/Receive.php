<?php

namespace NIKSEO\src;

class Receive
{
    private static $instance = null;

    public static function instantiate(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(){
        add_action('rest_api_init', function () {
            register_rest_route('sonic/v1', '/post/update', [
                'methods' => 'POST',
                'callback' => [$this, 'submit_post_handler'],
            ]);
        });
    }

    /**
     * @param $request
     * @return void
     */
    public function submit_post_handler($request) {
        $res = $this->validate_request($request);
        if($res['status'] !== 'success'){
            wp_send_json_error([
                'status' => 'failed',
                'message' => $res['message']
            ]);
        }else{
            $this->update($res);
        }
    }

    public function update($request){
        if(isset($request['post_update']) && $request['post_update'] === 'failed'){
            $this->cancel_post($request);
            return;
        }
        $html_content = isset($request['post_content']) ? $this->convertJsonToHtml($request['post_content']) : null;
        $image_url = $request['image'] ?? null;

        $post_meta = [];

        // add post meta description
        $client_seo_plugin = get_option('sonic-client-seo-plugin');
        if(in_array($client_seo_plugin, ['yoast', 'rank_math', 'all_in_one']) && $request['meta_description']){
            switch($client_seo_plugin){
                case 'rank_math':
                    $post_meta['rank_math_description'] = $request['meta_description'];
                    break;
                case 'all_in_one':
                    $post_meta['_aioseo_description'] = $request['meta_description'];
                    break;
                default:
                    $post_meta['_yoast_wpseo_metadesc'] = $request['meta_description'];
                    break;
            }
        }

        $post_meta['sonic_post_update'] = 'updated';
        $post_publisher = new PostPublisher();
        $update_res = $post_publisher->update($request['post_id'],null, $html_content,null,[],[],$image_url,$post_meta, $request['article_images']);

        if ($update_res) {
            wp_send_json_success([
                'status'  => 'success',
                'code' => 200,
                'response' => [
                    'message' => __('Post successfully created', 'nik-seo-agent'),
                    'res' => $request
                ],
            ]);
        } else {
            wp_send_json_error([
                'status'  => 'failed',
                'message' => __('Post creation failed.', 'nik-seo-agent'),
            ]);
        }

    }

    public function cancel_post($request)
    {
        $post_publisher = new PostPublisher();
        $post_publisher->update_status($request['post_id'], 'canceled-remote');
    }

    public function validate_request($request){
        $api_key = $request->get_header('api-key');
        $current_api_key = get_option('niklink_seo_api_key');

        if ($api_key !== $current_api_key) {
            return [
                'status' => 'failed',
                'message'=> __('API Key is not valid', 'sonic')
            ];
        }

        $request_body = json_decode($request->get_body(),true);
        $post = get_post($request_body['postID']);

        if(!$post){
            return [
                'status' => 'failed',
                'message'=> __('post ID is incorrect', 'sonic')
            ];
        } elseif ( get_post_meta($request_body['postID'], 'sonic_post_update', true) !== 'processing') {
            return [
                'status' => 'failed',
                'message'=> __('post is not authorized to edit', 'sonic')
            ];

        } else {
            return [
                'status' => 'success',
                'post_id' => $request_body['postID'],
                'post_content' => $request_body['postContent'] ?? null,
                'post_update' => $request_body['status'] ?? null,
                'image' => $request_body['image'] ?? null,
                'article_images' => $request_body['articleImages'] ?? null,
                'meta_description' => $request_body['metaDescription'] ?? null,
            ];
        }
    }

    /**
     * @param $json
     * @return string|void
     * After receive content from endpoint it should be converted into proper HTML
     */
    protected function convertJsonToHtml($html) {
        return html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    }
}