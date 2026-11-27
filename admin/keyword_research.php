<?php
$site_description = get_option('sonic-site-description');
$current_locale = get_locale();

// Get all installed languages
$languages = get_available_languages();
array_unshift($languages, 'en_US'); // Ensure default is included

// Optional: Map locale codes to readable names
function get_language_name($locale) {
    if (function_exists('locale_get_display_name')) {
        return locale_get_display_name($locale, $locale);
    } else {
        // Fallback: Just return the locale code
        return $locale;
    }
}

$show_hint = get_option('sonic_keywords');

$sonic_page_title = __('Assistant', 'sonic');
$sonic_page_subtitle = __('Discover post ideas tailored to your website and publish them instantly.', 'sonic');
require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-open.php';
?>

<div class="sonic-container">
    <?php if (!$site_description) : ?>
        <div class="sonic-notice sonic-notice-error">
            <?php echo __('to use this option first you have to write a description about your site in settings.', 'sonic'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=nik_seo')); ?>"><?php echo __('click here', 'sonic'); ?></a>
        </div>
    <?php else: ?>

        <div class="sonic-card sonic-assistant-toolbar">
            <form id="keyword-research" class="sonic-assistant-form">
                <div class="sonic-assistant-section">
                    <p class="sonic-assistant-label"><?php echo __('AI Model', 'sonic'); ?></p>
                    <div class="sonic-radio-group">
                        <?php $i = 0; foreach (MODELS as $model): ?>
                            <label class="sonic-radio-pill model-option" for="m-<?php echo esc_attr($model['name']); ?>">
                                <input type="radio" name="model"
                                       value="<?php echo esc_attr($model['value']); ?>"
                                       id="m-<?php echo esc_attr($model['name']); ?>"
                                    <?php checked($i, 0); ?>>
                                <span><?php echo esc_html($model['name']); ?></span>
                            </label>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>

                <div class="sonic-assistant-section">
                    <label class="sonic-field sonic-field-inline" for="language-select">
                        <span><?php echo __('Language', 'sonic'); ?></span>
                        <select name="lang" id="language-select" class="sonic-select">
                            <?php foreach ($languages as $locale): ?>
                                <option value="<?php echo esc_attr($locale); ?>" <?php selected($locale, $current_locale); ?>>
                                    <?php echo esc_html(get_language_name($locale)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="sonic-assistant-actions">
                    <button id="keyword-research-button" class="sonic-btn sonic-btn-primary blue" type="submit">
                        <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/logo.png'); ?>" alt="" />
                        <?php echo __('suggest post title', 'sonic'); ?>
                    </button>
                    <span class="nik-spinner hide"></span>
                    <button type="button" class="download-excel sonic-btn sonic-btn-secondary green hide" onclick="downloadCSV('research-table', [0], true)">
                        <?php echo __('Download Excel', 'sonic'); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12.5535 16.5061C12.4114 16.6615 12.2106 16.75 12 16.75C11.7894 16.75 11.5886 16.6615 11.4465 16.5061L7.44648 12.1311C7.16698 11.8254 7.18822 11.351 7.49392 11.0715C7.79963 10.792 8.27402 10.8132 8.55352 11.1189L11.25 14.0682V3C11.25 2.58579 11.5858 2.25 12 2.25C12.4142 2.25 12.75 2.58579 12.75 3V14.0682L15.4465 11.1189C15.726 10.8132 16.2004 10.792 16.5061 11.0715C16.8118 11.351 16.833 11.8254 16.5535 12.1311L12.5535 16.5061Z" fill="currentColor"/>
                            <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.5125 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <div id="keyword-research-result" class="sonic-card hide"></div>

        <!-- Create Post Settings Modal -->
        <div id="create-post-by-keyword-modal" class="sonic-modal create-modal" style="display:none;">
            <div class="sonic-modal-panel">
                <div class="keyword-modal-header sonic-modal-header">
                    <h2><?php echo __('Create Post', 'sonic'); ?></h2>
                    <a href="" id="advanced-content-create-button" target="_blank"><?php echo __('Go to advanced post create ›', 'sonic'); ?></a>
                </div>
                <form id="create-post-by-keyword" class="sonic-modal-body">
                    <div class="sonic-field">
                        <label for="keyword-title"><?php echo __('Article Title', 'sonic'); ?></label>
                        <input type="text" name="title" id="keyword-title" value="" class="sonic-input" required>
                    </div>

                    <div class="sonic-field">
                        <p class="sonic-field-label"><?php echo __('Select the Ai model', 'sonic'); ?></p>
                        <div class="sonic-radio-group">
                            <?php $i = 0; foreach (MODELS as $model): ?>
                                <label class="sonic-radio-pill" for="<?php echo esc_attr($model['name']); ?>">
                                    <input type="radio" name="model" value="<?php echo esc_attr($model['value']); ?>" id="<?php echo esc_attr($model['name']); ?>"
                                        <?php checked($i, 0); ?>>
                                    <span><?php echo esc_html($model['name']); ?></span>
                                </label>
                            <?php $i++; endforeach; ?>
                        </div>
                    </div>

                    <div class="sonic-field sonic-toggle-field">
                        <p class="sonic-field-label"><?php echo __('Do you need a featured image?', 'sonic'); ?></p>
                        <div class="featured-image-input">
                            <input type="checkbox" name="featured-image" value="1">
                            <small><?php echo __('2 tokens will be used', 'sonic'); ?></small>
                        </div>
                    </div>

                    <div class="sonic-modal-tab-adv-settings sonic-accordion-trigger" onclick="toggle_settings_tab(this)">
                        <p><?php echo __('Advanced Settings', 'sonic'); ?></p>
                    </div>

                    <div id="sonic-modal-adv-settings" class="hide">
                        <div class="sonic-field">
                            <p class="sonic-field-label"><?php echo __('Post Category', 'sonic'); ?></p>
                            <?php if (!empty($args['categories'])): ?>
                                <div class="category-select sonic-category-select-full">
                                    <?php foreach ($args['categories'] as $category): ?>
                                        <div><input type="checkbox" name="cats[]" value="<?php echo esc_attr($category['id']); ?>" <?php checked($category['default']); ?>>
                                            <span><?php echo esc_html($category['name']); ?></span>
                                        </div>
                                        <?php if (!empty($category['child'])): ?>
                                            <?php foreach ($category['child'] as $child): ?>
                                                <div><input class="child" type="checkbox" name="cats[]" value="<?php echo esc_attr($child['id']); ?>">
                                                    <span><?php echo esc_html($child['name']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="footer sonic-modal-footer">
                        <input type="hidden" name="row_id">
                        <button type="submit" class="sonic-btn sonic-btn-primary" id="keyword_create_post">
                            <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/logo.png'); ?>" alt="" />
                            <?php echo __('Create post', 'sonic'); ?>
                        </button>
                        <button type="button" class="sonic-btn sonic-btn-secondary close-popup" onclick="closeModal('create-post-by-keyword-modal')"><?php echo __('Cancel', 'sonic'); ?></button>
                        <span class="nik-spinner hide"></span>
                        <div class="alert alert-error hide"></div>
                    </div>
                </form>
            </div>
        </div>

        <div id="toast">
            <span><?php echo __('post is being generated!', 'sonic'); ?></span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=nik_seo_posts')); ?>" target="_blank"><?php echo __('show status', 'sonic'); ?></a>
        </div>

        <?php if($show_hint): ?>
            <div class="sonic-hint hide">
                <div class="content">
                    <div class="hint-text">
                        <?php echo HINTS['sonic_keywords']; ?>
                    </div>
                    <div class="hint-buttons">
                        <button type="button" onclick="closeHint()"><?php echo __('close hint', 'sonic'); ?></button>
                        <button type="button" class="disable-hint" hint_id="sonic_keywords"><?php echo __('Don`t show hint', 'sonic'); ?></button>
                    </div>
                </div>
                <div class="img">
                    <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/sonic-hint.png'); ?>" alt="">
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-close.php'; ?>
