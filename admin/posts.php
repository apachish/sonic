<?php
$show_hint = get_option('sonic_posts');

$sonic_page_title = __('Posts', 'sonic');
$sonic_page_subtitle = __('Track generated articles and update existing content.', 'sonic');
require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-open.php';

$processing_args = array(
    'post_type' => 'post',
    'posts_per_page' => 10,
    'meta_query' => array(
        array(
            'key' => 'sonic_post_update',
            'compare' => '=',
            'value' => 'processing',
        )
    ),
);

$process_query = new WP_Query($processing_args);
if ($process_query->have_posts()): ?>
    <div class="sonic-card sonik-posts-container updating">
        <div class="sonic-card-header">
            <h2><?php echo __('Updating Posts', 'sonic'); ?></h2>
        </div>
        <div class="sonic-table-wrap">
            <table class="wp-list-table widefat fixed striped sonic-table">
                <thead>
                    <tr>
                        <th><?php echo __('Title', 'sonic'); ?></th>
                        <th><?php echo __('Publish Date', 'sonic'); ?></th>
                        <th><?php echo __('Modify Date', 'sonic'); ?></th>
                        <th><?php echo __('Author', 'sonic'); ?></th>
                        <th><?php echo __('Actions', 'sonic'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($process_query->have_posts()):
                        $process_query->the_post(); ?>
                        <tr id="post_<?php the_ID(); ?>">
                            <td><a target="_blank" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_the_date('Y-m-d')); ?></td>
                            <td><?php echo esc_html(get_the_modified_date('Y-m-d')); ?></td>
                            <td><?php the_author(); ?></td>
                            <td class="nik-actions">
                                <button class="button open-cancel-update-popup sonic-btn sonic-btn-secondary sonic-btn-sm" data-post-id="<?php the_ID(); ?>">
                                    <?php echo __('Cancel Process', 'sonic'); ?>
                                </button>
                                <span class="nik-spinner"></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
wp_reset_postdata();
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

$all_args = array(
    'post_type' => 'post',
    'posts_per_page' => 10,
    'paged' => $paged,
    'orderby' => 'modified',
    'order' => 'ASC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'sonic_post_update',
            'compare' => 'NOT EXISTS',
        ),
        array(
            'key' => 'sonic_post_update',
            'value' => 'processing',
            'compare' => '!=',
        ),
    ),
);

$query = new WP_Query($all_args);

if ($query->have_posts()): ?>
    <div class="sonic-card sonik-posts-container total-posts">
        <div class="sonic-card-header">
            <h2><?php echo __('All Posts', 'sonic'); ?></h2>
        </div>
        <div class="sonic-table-wrap">
            <table class="wp-list-table widefat fixed striped sonic-table">
                <thead>
                    <tr>
                        <th><?php echo __('Title', 'sonic'); ?></th>
                        <th><?php echo __('Publish Date', 'sonic'); ?></th>
                        <th><?php echo __('Modify Date', 'sonic'); ?></th>
                        <th><?php echo __('Author', 'sonic'); ?></th>
                        <th><?php echo __('Actions', 'sonic'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($query->have_posts()):
                        $query->the_post();
                        $modify_date_timestamp = get_post_timestamp(null, 'modified');
                        $now = time();
                        $diff = $now - $modify_date_timestamp;
                        $first_break_time = 60 * 60 * 24 * 30 * 2;
                        $second_break_time = $first_break_time * 3;
                        if ($diff < $first_break_time) {
                            $post_date_label = '<span class="tag success-bg">' . __('New article', 'sonic') . '</span>';
                        } elseif ($diff < $second_break_time) {
                            $post_date_label = '<span class="tag warning-bg">' . __('Update suggested', 'sonic') . '</span>';
                        } else {
                            $post_date_label = '<span class="tag danger-bg">' . __('Old content', 'sonic') . '</span>';
                        }
                        ?>
                        <tr id="post_<?php the_ID(); ?>">
                            <td>
                                <a target="_blank" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                <?php echo $post_date_label; ?>
                            </td>
                            <td><?php echo esc_html(get_the_date('Y-m-d')); ?></td>
                            <td><?php echo esc_html(get_the_modified_date('Y-m-d')); ?></td>
                            <td><?php the_author(); ?></td>
                            <td class="nik-actions">
                                <button class="button open-update-popup sonic-btn sonic-btn-primary sonic-btn-sm" data-post-id="<?php the_ID(); ?>">
                                    <?php echo __('Update', 'sonic'); ?>
                                </button>
                                <span class="nik-spinner hide"></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="nik-seo-posts-table sonic-pagination">
        <?php
        $base_url = str_replace(999999999, '%#%', get_pagenum_link(999999999));
        $base_url = str_replace('&#038;', '&', $base_url);
        echo html_entity_decode(paginate_links(array(
            'base' => $base_url,
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $query->max_num_pages,
            'prev_text' => __('« Prev Page', 'sonic'),
            'next_text' => __('Next Page »', 'sonic'),
        )));
        ?>
    </div>

<?php else: ?>
    <div class="sonic-card sonic-empty-state">
        <p><?php echo __('No posts found.', 'sonic'); ?></p>
    </div>
<?php endif;

wp_reset_postdata();
?>

<?php if($show_hint): ?>
    <div class="sonic-hint hide">
        <div class="content">
            <div class="hint-text">
                <?php echo HINTS['sonic_posts']; ?>
            </div>
            <div class="hint-buttons">
                <button type="button" onclick="closeHint()"><?php echo __('close hint', 'sonic'); ?></button>
                <button type="button" class="disable-hint" hint_id="sonic_posts"><?php echo __('Don`t show hint', 'sonic'); ?></button>
            </div>
        </div>
        <div class="img">
            <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/sonic-hint.png'); ?>" alt="">
        </div>
    </div>
<?php endif; ?>

<!-- Update Popup -->
<div id="update-popup" class="confirm-modal sonic-modal sonik-body" style="display:none;">
    <div class="sonic-modal-panel">
        <div class="sonic-modal-header">
            <h2><?php echo __('Update Post', 'sonic'); ?></h2>
        </div>
        <form id="update-form" class="sonic-modal-body">
            <input type="hidden" name="post_id" id="popup-post-id" value="">
            <p><?php echo __('Specify the update type (2 tokens)', 'sonic'); ?></p>
            <div class="sonic-radio-group sonic-radio-group-stack">
                <label class="sonic-radio-pill" for="partial_update">
                    <input type="radio" name="update_type" value="partial" checked id="partial_update">
                    <span><?php echo __('Partial update', 'sonic'); ?></span>
                </label>
                <label class="sonic-radio-pill" for="whole_update">
                    <input type="radio" name="update_type" value="whole" id="whole_update">
                    <span><?php echo __('Whole article update', 'sonic'); ?></span>
                </label>
            </div>

            <p><?php echo __('Select the Ai model', 'sonic'); ?></p>
            <div class="sonic-radio-group sonic-radio-group-stack">
                <?php $i = 0; foreach (MODELS as $model): ?>
                    <label class="sonic-radio-pill" for="<?php echo esc_attr($model['name']); ?>">
                        <input type="radio" name="model" value="<?php echo esc_attr($model['value']); ?>" id="<?php echo esc_attr($model['name']); ?>"
                            <?php checked($i, 0); ?>>
                        <span><?php echo esc_html($model['name']); ?></span>
                    </label>
                <?php $i++; endforeach; ?>
            </div>

            <div id="whole-warning" class="sonic-notice sonic-notice-warning hide">
                <?php echo __('⚠️ Whole article updates are not recommended unless you know exactly what you are doing!', 'sonic'); ?>
            </div>

            <div class="footer sonic-modal-footer">
                <button type="submit" class="sonic-btn sonic-btn-primary">
                    <img src="<?php echo esc_url(NIKSEO_ASSETS . '/img/logo.png'); ?>" alt="" />
                    <?php echo __('Confirm', 'sonic'); ?>
                </button>
                <button type="button" class="sonic-btn sonic-btn-secondary close-popup"><?php echo __('Cancel', 'sonic'); ?></button>
                <span class="nik-spinner hide"></span>
                <div class="alert hide"></div>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Update Popup -->
<div id="cancel-update-popup" class="confirm-modal sonic-modal" style="display:none;">
    <div class="sonic-modal-panel">
        <div class="sonic-modal-header">
            <h2><?php echo __('Cancel update', 'sonic'); ?></h2>
        </div>
        <form id="cancel-update-form" class="sonic-modal-body">
            <input type="hidden" name="post_id" id="cancel-popup-post-id" value="">
            <div class="sonic-notice sonic-notice-warning">
                <?php echo __(' By this action, used tokens will not be refunded!', 'sonic'); ?>
            </div>
            <div class="footer sonic-modal-footer">
                <button type="submit" class="sonic-btn sonic-btn-primary confirm-btn-popup"><?php echo __('Confirm', 'sonic'); ?></button>
                <button type="button" class="sonic-btn sonic-btn-secondary close-cancel-popup"><?php echo __('Cancel', 'sonic'); ?></button>
                <span class="nik-spinner hide"></span>
                <div class="alert hide"></div>
            </div>
        </form>
    </div>
</div>

<?php require NIKSEO_PLUGIN_PATH . '/admin/partials/shell-close.php'; ?>
