<?php
$errors = [];
$saved_notice = '';
// Check user capabilities
if (!current_user_can('manage_options')) {
  return;
}
$show_hint = get_option('sonic_settings');
// Save settings if form submitted
if (isset($_POST['niklink_seo_api_key'])) {

    $api_key = sanitize_text_field($_POST['niklink_seo_api_key']);
    update_option('niklink_seo_api_key', $api_key);

    $saved_notice = __('API Key saved successfully.', 'sonic');
    if(isset($_POST['sonic-site-description'])){
        update_option('sonic-site-description', sanitize_text_field($_POST['sonic-site-description']));
    }
    if(isset($_POST['seo-plugin'])){
        update_option('sonic-client-seo-plugin', sanitize_text_field($_POST['seo-plugin']));
    }

}

// ERRORS

// check if REST is enable:
$request = new WP_REST_Request( 'POST', '/' );
$result = apply_filters( 'rest_authentication_errors', null, $request );
if ( is_wp_error( $result ) ) {
    $errors[] = __( 'REST API is disabled in your wordpress or theme or by any plugin.', 'sonic' );
}

// check if there is default category is set:
$default_publish_post_category = get_option('default_category');
if(!$default_publish_post_category){
    $errors[] = __( 'you should enable default publish post category. settings -> writing', 'sonic' );
}

// Get the current values
$saved_api_key = get_option('niklink_seo_api_key', '');
$site_description = get_option('sonic-site-description');
$client_seo_plugin = get_option('sonic-client-seo-plugin');

// check_api
$body = null;
if ($saved_api_key) {
  $res = wp_remote_post(NIK_USER_ASSET_ROUTE, [
    'method' => 'POST',
    'timeout' => 30,
    'headers' => [
      'content-type' => 'application/json',
      'api-key' => $saved_api_key,
    ],
    'body' => []
  ]);
  if (!is_wp_error($res)) {
    $body = json_decode(wp_remote_retrieve_body($res), true);
  }
}
if (is_array($body) && isset($body['status']) && $body['status'] === 'success') {
  if (!empty($body['tokens'])) {
    $status = 1;
  } else {
    $status = 2;
  }
} elseif ($saved_api_key) {
  $status = 3;
} else {
  $status = 4;
}

$sonic_page_title = __('Settings', 'sonic');
$sonic_page_subtitle = __('Connect your API key and configure Sonic for your site.', 'sonic');
require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-open.php';
?>

<div class="sonic-grid sonic-grid-settings sonic-settings">
    <div class="sonic-stack">
        <?php if (!empty($saved_notice)) : ?>
            <div class="sonic-notice sonic-notice-success">
                <p><?php echo esc_html($saved_notice); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($status == 4): ?>
            <div class="sonic-notice sonic-notice-info">
                <p><?php echo __('Before you start, you must receive your API code from Nicklink and then register it. To do this, simply click on the Get API KEY button at the bottom of this page.', 'sonic') ?></p>
            </div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
            <div class="sonic-errors">
                <?php foreach ($errors as $error): ?>
                    <div class="sonic-notice sonic-notice-error"><?php echo esc_html($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($status != 4): ?>
            <div class="sonic-card sonic-status-card">
                <?php if ($status == 1): ?>
                    <div class="sonic-status sonic-status-success">
                        <span class="sonic-status-label"><?php echo __('Account Status', 'sonic'); ?></span>
                        <span class="sonic-status-value"><?php echo __('Active', 'sonic'); ?></span>
                    </div>
                    <div class="sonic-status sonic-status-tokens">
                        <span class="sonic-status-label"><?php echo __('Remaining Tokens', 'sonic'); ?></span>
                        <span class="sonic-status-value"><?php echo esc_html($body['tokens']); ?></span>
                    </div>
                <?php elseif ($status == 2): ?>
                    <div class="sonic-notice sonic-notice-warning">
                        <?php echo __('Insufficient tokens. in order to use the application you have to buy more tokens.', 'sonic') ?>
                    </div>
                <?php else: ?>
                    <div class="sonic-notice sonic-notice-error">
                        <?php echo __('API key is not valid or the account suspended', 'sonic') ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="sonic-stack">
            <div class="sonic-card">
                <div class="sonic-card-header">
                    <h2><?php echo __('API Key', 'sonic'); ?></h2>
                    <p><?php echo __('Your Nicklink API key unlocks all Sonic tools.', 'sonic'); ?></p>
                </div>
                <div class="sonic-field">
                    <label for="niklink_seo_api_key"><?php echo __('API Key', 'sonic'); ?></label>
                    <input
                        type="password"
                        id="niklink_seo_api_key"
                        name="niklink_seo_api_key"
                        value="<?php echo esc_attr($saved_api_key); ?>"
                        class="sonic-input"
                        autocomplete="off"
                    />
                </div>
            </div>

            <div class="sonic-card">
                <div class="sonic-card-header">
                    <div class="sonic-card-header-row">
                        <div>
                            <h2><?php echo __('Site Description', 'sonic'); ?></h2>
                            <p><?php echo __('This option is required for Assistant tools', 'sonic'); ?></p>
                        </div>
                        <button
                            type="button"
                            id="sonic_site_description_suggest"
                            class="sonic-btn sonic-btn-secondary sonic-btn-sm"
                        >
                            <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/logo.png'); ?>" alt="" />
                            <?php echo __('Auto generate','sonic'); ?>
                        </button>
                        <span class="nik-spinner hide spinner-30"></span>
                    </div>
                </div>
                <div class="sonic-field">
                    <textarea
                        id="sonic-site-description"
                        name="sonic-site-description"
                        rows="5"
                        class="sonic-textarea"
                        placeholder="<?php echo esc_attr__('describe your business in a few sentences', 'sonic'); ?>"
                    ><?php echo esc_textarea($site_description); ?></textarea>
                </div>
            </div>

            <div class="sonic-card">
                <div class="sonic-card-header">
                    <h2><?php echo __('Seo Plugin', 'sonic'); ?></h2>
                    <p><?php echo __('If you have Seo Plugin Select it here', 'sonic'); ?></p>
                </div>
                <div class="sonic-field">
                    <label for="seo_plugin"><?php echo __('Seo Plugin', 'sonic'); ?></label>
                    <select name="seo-plugin" id="seo_plugin" class="sonic-select">
                        <option value="none" <?php selected($client_seo_plugin, 'none'); ?>><?php echo __('None', 'sonic');?></option>
                        <option value="yoast" <?php selected($client_seo_plugin, 'yoast'); ?>><?php echo __('Yoast', 'sonic');?></option>
                        <option value="rank_math" <?php selected($client_seo_plugin, 'rank_math'); ?>><?php echo __('Rank Math', 'sonic');?></option>
                        <option value="all_in_one" <?php selected($client_seo_plugin, 'all_in_one'); ?>><?php echo __('All in ONE', 'sonic');?></option>
                    </select>
                </div>
            </div>

            <div class="sonic-form-actions">
                <?php submit_button(__('Save Settings', 'sonic'), 'primary sonic-btn sonic-btn-primary', 'submit', false); ?>
            </div>
        </form>

        <div class="sonic-card sonic-card-actions">
            <div class="sonic-card-header">
                <h2><?php echo __('Quick Links', 'sonic'); ?></h2>
                <p><?php echo __('Get your API key or watch the tutorial to get started.', 'sonic'); ?></p>
            </div>
            <div class="buttons">
                <a href="<?php echo esc_url(SONIC_END_POINT . '/nik-shop/sonikagent'); ?>" target="_blank" class="sonik-btn sonik-start-btn sonic-btn-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                    <?php echo __('View tutorial', 'sonic'); ?>
                </a>
                <a href="<?php echo esc_url(SONIC_END_POINT . '/my-account/nik-account-settings'); ?>" target="_blank" class="sonik-btn-2"><?php echo __('Get API key', 'sonic'); ?></a>
            </div>
        </div>
    </div>

    <aside class="sonic-aside">
        <div class="sonic-card sonic-card-hero">
            <div class="sonic-hero-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                </svg>
            </div>
            <div class="sonic-hero-copy">
                <h3><?php echo __('Your AI content agent', 'sonic'); ?></h3>
                <p><?php echo __('Generate SEO-optimized posts, research keywords, and keep your content fresh — all from WordPress.', 'sonic'); ?></p>
            </div>
        </div>
    </aside>
</div>

<?php if($show_hint): ?>
    <div class="sonic-hint hide">
        <div class="content">
            <div class="hint-text">
                <?php echo HINTS['sonic_settings']; ?>
            </div>
            <div class="hint-buttons">
                <button type="button" onclick="closeHint()"><?php echo __('close hint', 'sonic'); ?></button>
                <button type="button" class="disable-hint" hint_id="sonic_settings"><?php echo __('Don`t show hint', 'sonic'); ?></button>
            </div>
        </div>
        <div class="img">
            <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/sonic-hint.png'); ?>" alt="">
        </div>
    </div>
<?php endif; ?>

<?php require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-close.php'; ?>
