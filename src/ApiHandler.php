<?php

namespace NIKSEO\src;

use NIKSEO\callbacks\NikCallBacks;
use WP_Query;

class ApiHandler extends NikCallBacks
{
    private static $instance = null;
    private string $nik_seo_api_key;

    public static function instantiate(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(){
        if(!$api_key = get_option('niklink_seo_api_key')){
            $this->admin_notice(__('Please insert your API key in settings', 'sonic'));
        }
        $this->nik_seo_api_key = $api_key ?: '';

        add_action('wp_ajax_data_receiver', [$this, 'handle_request']);
    }

    /**
     * @return void
     * Handle request of AI content creation
     */
    public function handle_request() {
        if(!$this->nik_seo_api_key){
            wp_send_json_error([
                'status' => 'failed',
                'response' =>[
                    'message' => __('Please insert your API key in settings', 'sonic')
                ]
            ]);
        }
        // Sanitize data received from client
        $data = $this->data_sanitizer($_POST);
        if($data['type'] == 'update'){
            $this->update_article($data);
            exit();
        }
        if($data['type'] == 'cancel-update'){
            $this->cancel_update($data);
            exit();
        }
        if($data['type'] == 'check_status'){
            $this->return_processing_posts();
            exit();
        }
        if($data['type'] == 'keyword_research'){
            $this->return_keyword_research($data);
            exit();
        }
        if($data['type'] == 'site_description'){
            $this->return_site_description($data);
            exit();
        }
        if($data['type'] == 'disable_hint'){
            $this->disable_hint($data);
            exit();
        }

        //create temp post
        if(!$data['title']){
            wp_send_json_error([
                'status' => 'failed',
                'response' => [
                    'message' => __('Title is required', 'sonic')
                ],

            ]);
        }
        $data['post-status'] = $data['post-status'] ?? 'publish';
        $post_status = in_array($data['post-status'], ['publish', 'draft', 'pending']) ? $data['post-status'] : 'publish';
        $post_publisher = new PostPublisher();
        $data['postID'] = $post_publisher->publish($data['title'], null, $post_status, 'post', [], $data['cats'], null, ['sonic_post_update' => 'processing'], $data['slug']);

        if($data['postID']){
            // Prepare request arguments
            $args = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'api-key'      => $this->nik_seo_api_key,
                ],
                'body'    => json_encode($data),
                'timeout' => 300,
            ];

            // Send data to endpoint
            $response = wp_remote_post(NIK_CONTENT_CREATE_ROUTE, $args);

            // Check for WP error
            if (is_wp_error($response)) {
                wp_send_json_error([
                    'status'  => 'failed',
                    'response' => [
                        'message' => $response->get_error_message(),
                    ]
                ]);
                return;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            if ($response_code === 200) {
                if(defined('SOINC_DIRECT_REQUEST') && SOINC_DIRECT_REQUEST){
                    wp_send_json_success([
                        'status'  => 'success',
                        'response' => $response_body,
                    ]);
                }
                wp_send_json_success([
                    'status'  => 'success',
                    'code' => 200,
                    'response' => [
                        'message' => __('Success! Post is being Generated, You can leave this page.', 'sonic'),
                    ],
                ]);
            } else {
                $post_publisher->delete($data['postID']);
                wp_send_json_error([
                    'status'   => 'failed',
                    'code'     => $response_code,
                    'response' => json_decode($response_body),
                ]);
            }
        }else{
            wp_send_json_error([
                'status'   => 'failed',
                'response' => [
                    'message' => __('Post didnt create, please try again', 'sonic'),
                ]
            ]);
        }
    }


    private function update_article($data){

        if($data['updateType'] == 'partial'){
            $data['partialUpdate'] = 1;
        }

        // remove unnecessary payload
        unset($data['type']);
        unset($data['updateType']);

        // add unnecessary payload
        $data['update'] = 1;

        if(is_array($data['postId'])){
            $post_publisher = new PostPublisher();
            foreach($data['postId'] as $post_id){
                $post = $post_publisher->get_post($post_id);
                if($post){
                    $data['title'] = $post['title'];
                    $data['postContent'] = $post['postContent'];
                    $data['postID'] = $post_id;
                    $post_publisher->update_status($post_id, 'processing');
                    // Prepare request arguments
                    $args = [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'api-key'      => $this->nik_seo_api_key,
                        ],
                        'body'    => json_encode($data),
                        'timeout' => 300,
                    ];

                    // Send data to endpoint
                    $response = wp_remote_post(NIK_CONTENT_CREATE_ROUTE, $args);
                    // Check for WP error
                    if (is_wp_error($response)) {
                        wp_send_json_error([
                            'status'  => 'failed',
                            'message' => $response->get_error_message(),
                        ]);
                        return;
                    }

                    $response_code = wp_remote_retrieve_response_code($response);
                    $response_body = wp_remote_retrieve_body($response);
                    if ($response_code === 200) {
                        if(defined('SOINC_DIRECT_REQUEST') && SOINC_DIRECT_REQUEST === true){
                            wp_send_json_success([
                                'status'  => 'success',
                                'response' => $response_body,
                            ]);
                        }
                        wp_send_json_success([
                            'status'  => 'success',
                            'code' => 200,
                            'response' => [
                                'message' => __('Post is being Updated, You can leave this page. we will inform you once it is ready', 'sonic'),
                            ],
                        ]);
                    } else {
                        wp_send_json_error([
                            'status'   => 'failed',
                            'code'     => $response_code,
                            'response' => json_decode($response_body),
                        ]);
                    }
                }
            }
        }
    }

    private function cancel_update($data){
        if(is_array($data['postId'])){
            $post_publisher = new PostPublisher();
            foreach($data['postId'] as $post_id){
                $post_publisher->update_status($post_id, '');
            }
            wp_send_json_success([
                'status'  => 'success',
                'code' => 200,
                'response' => [
                    'message' => __('Update canceled', 'sonic'),
                ],
            ]);
        }
    }

    private function return_processing_posts(){
        $processing_query = new WP_Query( array(
            'post_type'      => 'post',
            'meta_query'     => array(
                array(
                    'key'     => 'sonic_post_update',
                    'value'   => 'processing',
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1, // optional: gets all matching posts
        ) );

        if ( $processing_query->have_posts() ) {

            $processing_post_ids = [];
            while ( $processing_query->have_posts() ) {
                $processing_query->the_post();
                $processing_post_ids[] = get_the_ID();
            }
            wp_send_json_success([
                'status' => 'success',
                'posts'  => $processing_post_ids, // send array of IDs
            ]);
            wp_reset_postdata();

        }else{

            wp_reset_postdata();
            wp_send_json_error([
                'status'  => 'failed',
                'message' => __('No posts found', 'sonic'),
            ]);
        }

    }

    public function return_keyword_research($data)
    {
        unset($data['type']);

        if(!$data['siteDescription']){
            wp_send_json_error([
                'status' => 'failed',
                'response' => [
                    'message' => __('Please fill site description in settings', 'sonic')
                ]
            ]);
        }

        $post_publisher = new PostPublisher();
        $data['blogTitles'] = $post_publisher->get_posts();
        $data['keywordResearch'] = 1;
        $data['lang'] = sanitize_text_field($data['lang']);
        $data['model'] = sanitize_text_field($data['model']);

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key'      => $this->nik_seo_api_key,
            ],
            'body'    => json_encode($data),
            'timeout' => 300,
        ];

        // Send data to endpoint
        $response = wp_remote_post(NIK_KEYWORD_RESEARCH_ROUTE, $args);

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if ($response_code === 200) {
            $response = json_decode($response_body, true);
            if($response['success']){
                wp_send_json_success([
                    'status' => 'success',
                    'response' => [
                        'keywords' => $response['data'],
                    ]
                ]);
            }else{
                wp_send_json_error([
                    'status' => 'failed',
                    'response' => [
                        'message' => $response['data']['message'],
                    ]
                ]);
            }

        } else {
            wp_send_json_error([
                'status' => 'failed',
                'response' => [
                    'message' => __('Some thing went Wrong! please try again later or choose another model!', 'sonic'),
                ],

            ]);
        }
    }

    public function disable_hint($data)
    {
        $res = update_option($data['id'], 0);
        wp_send_json_success([
            'status' => 'success',
            'response' => $res
        ]);
    }

    /**
     * @param $data
     * @return mixed
     * Prepare data to send to the endpoint
     */
    public function data_sanitizer($data)
    {
        unset($data['action']);
        if(is_array($data['relevant-title']) && is_array($data['relevant-link']) && count($data['relevant-title']) == count($data['relevant-link'])){
            $links = [];
            foreach($data['relevant-title'] as $key => $title){
                $links[] = [
                    'title' => $title,
                    'link' => $_POST['relevant-link'][$key]
                ];
            }
            unset($data['relevant-title']);
            unset($data['relevant-link']);
            $data['custom-links'] = $links;
        }

        if($data['min-words']){
            $data['minWords'] = $data['min-words'];
            unset($data['min-words']);
        }

        if($data['max-words']){
            $data['maxWords'] = $data['max-words'];
            unset($data['max-words']);
        }

        if($data['custom-links']){
            $data['customLinks'] = $data['custom-links'];
            unset($data['custom-links']);
        }

        if($data['featured-image']){
            $data['featuredImage'] = 1;
            unset($data['featured-image']);
        }

        if($data['custom-prompt']){
            $data['customPrompt'] = $data['custom-prompt'];
            unset($data['custom-prompt']);
        }

        if($data['article-img']){
            $data['articleImages'] = $data['article-img'];
            unset($data['article-img']);
        }

        if($data['meta-description-input']){
            $data['metaDescription'] = 1;
            unset($data['meta-description-input']);
        }

        $site_description = get_option('sonic-site-description');
        if($site_description){
            $data['siteDescription'] = $site_description;
        }

        $data['origin'] = home_url();

        return $data;
    }

    /**
     * @param $json
     * @return string|void
     * After receive content from endpoint it should be converted into proper HTML
     */
    protected function convertJsonToHtml($data) {

        // Check if the JSON was valid and contains 'body'
        if (isset($data['status']) && $data['status'] === 'success' && isset($data['body'])) {
            // Decode any escaped Unicode characters
            $html = $data['body'];

            // Optional: decode HTML entities (if necessary)
            return html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        }
    }

    private function return_site_description(mixed $data){

        $post_publisher = new PostPublisher();
        $data['blogTitles'] = $post_publisher->get_posts();
        $data['generateDescription'] = 1;
        $data['lang'] = get_locale();
        $data['model'] = 'gemini';

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'api-key'      => $this->nik_seo_api_key,
            ],
            'body'    => json_encode($data),
            'timeout' => 300,
        ];
        $response = wp_remote_post(NIK_TOOLS, $args);

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if ($response_code === 200) {
            $response = json_decode($response_body, true);
            if($response['success']){
                wp_send_json_success([
                    'status' => 'success',
                    'response' => [
                        'siteDescription' => $response['data']['response'],
                    ]
                ]);
            }else{
                wp_send_json_error([
                    'status' => 'failed',
                    'response' => [
                        'message' => $response['data']['message'],
                    ]
                ]);
            }

        } else {
            wp_send_json_error([
                'status' => 'failed',
                'response' => [
                    'message' => __('Some thing went Wrong! please try again later or choose another model!', 'sonic'),
                ],

            ]);
        }
    }
}