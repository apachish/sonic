<?php
// Get current locale
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
if(isset($_GET['title'])){
    $selected_title = sanitize_text_field($_GET['title']);
}

$client_seo_plugin = get_option('sonic-client-seo-plugin');
if(in_array($client_seo_plugin, ['yoast', 'rank_math', 'all_in_one'])){
    $meta_description = true;
}else{
    $meta_description = false;
}

$show_hint = get_option('sonic_content_create');

$sonic_page_title = __('Create Post', 'sonic');
$sonic_page_subtitle = __('Build an SEO-ready article step by step with your preferred settings.', 'sonic');
require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-open.php';
?>

<section class="sonic-card sonic-card-form main-page active sonik-page sonik-page-3">
    <main>
        <section class="sonik-page-container" id="formContent">
            <form class="formsteps" id="formSteps">
                <!-- step 1 -->
                <div class="col-2">
                    <label class="form__label--title-question active form-group-input sonic-field" data-stage="0" data-substep="0">
                        <div class="sonic-field-label">
                            <?php echo __('Specify the article title', 'sonic') ?> <span class="sonic-required">*</span>
                            <small>
                                <?php echo __('The article title is the most important factor in determining SEO. You can use <a target="_blank" href="https://niklinkagency.com/blog/search-engine-optimization/choosing-an-article-title-for-seo-success/">this article</a> for guidance in selecting one.', 'sonic') ?></small>
                        </div>
                        <input type="text" name="title" value="<?php echo esc_attr($selected_title ?? ''); ?>" class="sonic-input">
                    </label>

                    <label class="form__label--title-question active form-group-input sonic-field" data-stage="0" data-substep="0">
                        <div class="sonic-field-label">
                            <?php echo __('Article slug', 'sonic') ?>
                            <small><?php echo __('You can leave it empty', 'sonic') ?></small>
                        </div>
                        <input type="text" name="slug" class="sonic-input">
                    </label>
                </div>

                <label class="form__label--title-question active form-group-input sonic-field" data-stage="0" data-substep="0" id="fakeinput_keywords">
                    <div class="sonic-field-label">
                        <?php echo __('Specify your keywords', 'sonic') ?>
                        <small><?php echo __('For example: buy mobile phone, online English training, online gold purchase, water pump price(Separate keywords with ENTER)', 'sonic') ?></small>
                    </div>
                    <div class="tag-container sonic-tag-container" data-tag-type="keywords">
                        <input type="text" id="keyword-input" class="tag-input sonic-input" data-tag-type="keywords" />
                        <div class="tags-wrapper d-flex"></div>
                    </div>
                </label>

                <label class="form__label--title-question active sonic-field" data-stage="0" data-substep="0">
                    <div class="sonic-field-label">
                        <?php echo __('Specify the article category', 'sonic') ?>
                        <small><?php echo __('Select the category in which the article will be published.', 'sonic') ?></small>
                    </div>
                    <?php if (!empty($args['categories'])): ?>
                        <div class="category-select">
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
                </label>

                <label class="form__label--title-question sonic-field" data-stage="0" data-substep="1">
                    <div class="sonic-field-label"><?php echo __('What should the tone of the article be?', 'sonic') ?></div>
                    <div class="radio-inputs sonic-radio-group">
                        <label class="sonic-radio-pill">
                            <input type="radio" name="tone" value="warm">
                            <span><?php echo __('Friendly', 'sonic') ?></span>
                        </label>
                        <label class="sonic-radio-pill">
                            <input type="radio" name="tone" value="official">
                            <span><?php echo __('Formal', 'sonic') ?></span>
                        </label>
                    </div>
                </label>

                <label class="form__label--title-question form-group-input sonic-field" data-stage="0" data-substep="1">
                    <div class="sonic-field-label"><?php echo __('Minimum word count', 'sonic') ?></div>
                    <input type="number" name="min-words" class="sonic-input">
                </label>

                <label class="form__label--title-question form-group-input sonic-field" data-stage="0" data-substep="1">
                    <div class="sonic-field-label"><?php echo __('Maximum word count', 'sonic') ?></div>
                    <input type="number" name="max-words" class="sonic-input">
                </label>

                <label class="form__label--title-question form-group-input sonic-field" data-stage="0" data-substep="1">
                    <div class="sonic-field-label">
                        <?php echo __('Who is your audience?', 'sonic') ?>
                        <small><?php echo __('For example: young people, professionals, parents, students, or fans', 'sonic') ?></small>
                    </div>
                    <input type="text" name="audience" class="sonic-input">
                </label>

                <label class="form__label--title-question form-blog-links sonic-field" data-stage="0" data-substep="2">
                    <div class="sonic-field-label">
                        <?php echo __('Specify related links in the article', 'sonic') ?>
                        <small><?php echo __('Specify the links you want to be referenced in the article', 'sonic') ?></small>
                    </div>
                    <div class="related-links add-box">
                        <a class="g-add-row" aria-label="<?php esc_attr_e('Add link', 'sonic'); ?>">
                            <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/plus.png'); ?>" alt="" />
                        </a>
                    </div>
                </label>

                <div class="col-2">
                    <label class="form__label--title-question form-group-input sonic-field" data-stage="0" data-substep="3">
                        <div class="sonic-field-label"><?php echo __('how many images do you need inside article?', 'sonic') ?></div>
                        <div class="featured-image-input">
                            <input type="text" name="article-img" value="0" class="sonic-input">
                        </div>
                    </label>
                    <div class="col-2">
                        <?php if($meta_description): ?>
                            <label class="form__label--title-question featured-img sonic-field sonic-toggle-field" data-stage="0" data-substep="3">
                                <div class="sonic-field-label"><?php echo __('Do you need meta description?', 'sonic') ?></div>
                                <div class="featured-image-input">
                                    <input type="checkbox" name="meta-description-input" value="1" checked>
                                    <small><?php echo __('Yes', 'sonic') ?></small>
                                </div>
                            </label>
                        <?php endif; ?>
                        <label class="form__label--title-question featured-img sonic-field sonic-toggle-field" data-stage="0" data-substep="3">
                            <div class="sonic-field-label"><?php echo __('Do you need a featured image?', 'sonic') ?></div>
                            <div class="featured-image-input">
                                <input type="checkbox" name="featured-image" value="1">
                                <small><?php echo __('1 more token will be used', 'sonic') ?></small>
                            </div>
                        </label>
                    </div>
                </div>

                <label class="form__label--title-question sonic-field" data-stage="0" data-substep="3">
                    <div class="sonic-field-label"><?php echo __('Which Ai should be used?', 'sonic') ?></div>
                    <div class="radio-inputs sonic-radio-group">
                        <label class="sonic-radio-pill">
                            <input type="radio" name="model" value="gpt" checked>
                            <span><?php echo __('ChatGPT', 'sonic') ?></span>
                        </label>
                        <label class="sonic-radio-pill">
                            <input type="radio" name="model" value="gemini">
                            <span><?php echo __('Gemini', 'sonic') ?></span>
                        </label>
                    </div>
                </label>

                <label class="form__label--title-question sonic-field" data-stage="0" data-substep="3">
                    <div class="sonic-field-label"><?php echo __('Specify the post publication status.', 'sonic') ?></div>
                    <div class="radio-inputs sonic-radio-group sonic-radio-group-wrap">
                        <label class="sonic-radio-pill">
                            <input type="radio" name="post-status" value="publish" checked>
                            <span><?php echo __('Publish', 'sonic') ?></span>
                        </label>
                        <label class="sonic-radio-pill">
                            <input type="radio" name="post-status" value="draft">
                            <span><?php echo __('Draft', 'sonic') ?></span>
                        </label>
                        <label class="sonic-radio-pill soon">
                            <input type="radio" name="post-status" value="pending" disabled>
                            <span><?php echo __('Schedule publication (coming soon)', 'sonic') ?></span>
                        </label>
                        <label class="sonic-field sonic-field-inline">
                            <select name="lang" id="language-select" class="sonic-select">
                                <?php foreach ($languages as $locale): ?>
                                    <option value="<?php echo esc_attr($locale); ?>" <?php selected($locale, $current_locale); ?>>
                                        <?php echo esc_html(get_language_name($locale)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span><?php echo __('Select language', 'sonic') ?></span>
                        </label>
                    </div>
                </label>

                <label class="form__label--title-question sonic-field" data-stage="0" data-substep="3">
                    <div class="sonic-field-label"><?php echo __('Custom prompt', 'sonic') ?></div>
                    <textarea name="custom-prompt" rows="5" class="sonic-textarea" placeholder="<?php echo esc_attr__('if you have custom instructions for the post generation insert it here (optional)', 'sonic'); ?>"></textarea>
                </label>

                <div class="progress-block">
                    <div class="progress-bar hide"></div>
                    <div class="single-request hide">
                        <div class="loader-text">
                            <?php echo __('Creating content', 'sonic') ?>
                        </div>
                        <div class="nik-spinner"></div>
                    </div>
                    <div class="result hide"></div>
                </div>

                <input type="hidden" name="request-type" value="single-request">

                <div class="btn-cnt buttons final-step sonic-form-actions">
                    <button type="button" class="tmpl-2 prev-btn sonik-btn-2" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                        <?php echo __('Previous Step', 'sonic') ?>
                    </button>
                    <button type="button" class="tmpl-1 next-btn sonik-btn sonik-start-btn"><?php echo __('Next Step', 'sonic') ?></button>
                </div>
            </form>

            <div class="steps-1 sonic-stepper" id="verticalSteps">
                <i class="active" data-step="1">1</i>
                <div class="line"></div>
                <i data-step="2">2</i>
                <div class="line"></div>
                <i data-step="3">3</i>
                <div class="line"></div>
                <i data-step="4">4</i>
            </div>
        </section>
    </main>
</section>

<?php if($show_hint): ?>
    <div class="sonic-hint hide">
        <div class="content">
            <div class="hint-text">
                <?php echo HINTS['sonic_content_create']; ?>
            </div>
            <div class="hint-buttons">
                <button type="button" onclick="closeHint()"><?php echo __('close hint', 'sonic'); ?></button>
                <button type="button" class="disable-hint" hint_id="sonic_content_create"><?php echo __('Don`t show hint', 'sonic'); ?></button>
            </div>
        </div>
        <div class="img">
            <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/sonic-hint.png'); ?>" alt="">
        </div>
    </div>
<?php endif; ?>

<?php require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-close.php'; ?>
