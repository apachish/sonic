<?php
if (!current_user_can('manage_options')) {
    return;
}

$sonic_current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'nik_seo';

$sonic_nav_items = [
    'nik_seo' => [
        'label' => __('Settings', 'sonic'),
        'url'   => admin_url('admin.php?page=nik_seo'),
        'icon'  => 'settings',
    ],
    'nik_seo_content_creator' => [
        'label' => __('Create Post', 'sonic'),
        'url'   => admin_url('admin.php?page=nik_seo_content_creator'),
        'icon'  => 'create',
    ],
    'nik_keyword_research' => [
        'label' => __('Assistant', 'sonic'),
        'url'   => admin_url('admin.php?page=nik_keyword_research'),
        'icon'  => 'assistant',
    ],
    'nik_seo_posts' => [
        'label' => __('Posts', 'sonic'),
        'url'   => admin_url('admin.php?page=nik_seo_posts'),
        'icon'  => 'posts',
    ],
];

$sonic_page_title = $sonic_page_title ?? __('Sonic', 'sonic');
$sonic_page_subtitle = $sonic_page_subtitle ?? '';
?>
<div class="wrap sonic-app sonik-body">
    <div class="sonic-shell">
        <aside class="sonic-sidebar" aria-label="<?php esc_attr_e('Sonic navigation', 'sonic'); ?>">
            <div class="sonic-sidebar-brand">
                <span class="sonic-sidebar-logo" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                    </svg>
                </span>
                <div class="sonic-sidebar-brand-text">
                    <strong>Sonic</strong>
                    <span><?php echo esc_html__('AI Content Agent', 'sonic'); ?></span>
                </div>
            </div>

            <nav class="sonic-sidebar-nav">
                <?php foreach ($sonic_nav_items as $slug => $item) : ?>
                    <a
                        href="<?php echo esc_url($item['url']); ?>"
                        class="sonic-sidebar-link<?php echo $sonic_current_page === $slug ? ' is-active' : ''; ?>"
                    >
                        <span class="sonic-sidebar-icon" aria-hidden="true">
                            <?php if ($item['icon'] === 'settings') : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                            <?php elseif ($item['icon'] === 'create') : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <?php elseif ($item['icon'] === 'assistant') : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                            <?php else : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            <?php endif; ?>
                        </span>
                        <?php echo esc_html($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sonic-sidebar-footer">
                <a href="<?php echo esc_url(SONIC_END_POINT . '/nik-shop/sonikagent'); ?>" target="_blank" rel="noopener" class="sonic-sidebar-help">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/></svg>
                    <?php echo esc_html__('Help & Tutorial', 'sonic'); ?>
                </a>
            </div>
        </aside>

        <div class="sonic-main">
            <header class="sonic-page-header">
                <div class="sonic-page-header-text">
                    <h1 class="sonic-page-title"><?php echo esc_html($sonic_page_title); ?></h1>
                    <?php if ($sonic_page_subtitle) : ?>
                        <p class="sonic-page-subtitle"><?php echo esc_html($sonic_page_subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </header>

            <div class="sonic-page-body">
